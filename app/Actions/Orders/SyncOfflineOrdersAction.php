<?php

namespace App\Actions\Orders;

use App\DTOs\OrderData;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\OrderService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Class SyncOfflineOrdersAction
 * 
 * Securely processes a batch of offline orders originating from the Vue PWA.
 * Features:
 * - DTO Enforcement (Strict typing for messy offline payloads)
 * - Idempotency (Prevents duplicate offline syncs using UUIDs)
 * - Event Suppression (Temporarily disables model cache-busting to prevent Redis/File I/O thrashing)
 * - N+1 Query Elimination (Pre-fetches managers for batch notifications)
 */
class SyncOfflineOrdersAction
{
    /**
     * Execute the batch synchronization.
     * 
     * @param array $payloads An array of raw associative arrays from the API.
     * @param User $user The user attempting the sync.
     * @return array The strict results array expected by the legacy PWA.
     */
    public static function execute(array $payloads, User $user): array
    {
        $syncedIds = [];
        $requiresApproval = [];
        $failedIds = [];
        $createdRequestIds = [];

        // 1. Pre-fetch Managers to avoid N+1 queries during notification dispatch
        $managers = collect();
        $canBypass = $user->hasPermission('accept_reject_order');
        if (!$canBypass) {
            $managers = User::whereHas('role', function($q) {
                $q->whereHas('permissions', function($p) {
                    $p->where('permission_name', 'accept_reject_order');
                });
            })->get();
        }

        // 2. Temporarily suppress the UpdatesPosSyncTimestamp trait to prevent Cache thrashing 
        // during a 50+ order bulk sync. We will manually clear the cache once at the very end.
        Order::withoutEvents(function () use ($payloads, $user, $canBypass, &$syncedIds, &$requiresApproval, &$failedIds, &$createdRequestIds) {
            
            foreach ($payloads as $offlinePayload) {
                $uuid = $offlinePayload['uuid'] ?? null;
                if (!$uuid) {
                    continue; // Cannot process without a traceability UUID
                }

                try {
                    DB::transaction(function () use ($offlinePayload, $uuid, $user, $canBypass, &$syncedIds, &$requiresApproval, &$createdRequestIds) {
                        
                        // Idempotency Check: Did we already sync this specific UUID?
                        $existingRequest = null;
                        if ($canBypass) {
                            $existingOrder = Order::where('uuid', $uuid)->first();
                            if ($existingOrder) {
                                $syncedIds[$uuid] = $existingOrder->id;
                                $requiresApproval[$uuid] = false;
                                return; // Already processed, safely skip
                            }
                        } else {
                            $existingRequest = OrderRequest::where('uuid', $uuid)->first();
                            if ($existingRequest) {
                                if ($existingRequest->status == 2) {
                                    // Order was previously rejected! We will resurrect it by updating it below.
                                } else {
                                    $syncedIds[$uuid] = $existingRequest->id;
                                    $requiresApproval[$uuid] = true;
                                    return; // Already processed, safely skip
                                }
                            }
                        }

                        // Unified Graph Sync: Handle Offline Customer Creation safely
                        if (!empty($offlinePayload['new_customer']) && !empty($offlinePayload['new_customer']['phone'])) {
                            $custData = $offlinePayload['new_customer'];
                            $customer = Customer::updateOrCreate(
                                ['phone' => $custData['phone']],
                                [
                                    'uuid' => $custData['uuid'] ?? null,
                                    'name' => $custData['name'],
                                    'email' => $custData['email'] ?? null,
                                    'tax_number' => $custData['tax_number'] ?? null,
                                    'address' => $custData['address'] ?? null,
                                    'is_active' => 1
                                ]
                            );
                            $offlinePayload['customer_id'] = $customer->id;
                            $offlinePayload['phone_number'] = $custData['phone'];
                        } elseif (!empty($offlinePayload['phone_number'])) {
                            // Fallback to searching by phone if explicitly passed
                            $dbCustomer = Customer::where('phone', $offlinePayload['phone_number'])->first();
                            if ($dbCustomer) {
                                $offlinePayload['customer_id'] = $dbCustomer->id;
                            }
                        }

                        // Cast the raw array payload into our strictly typed Enterprise DTO
                        $dto = OrderData::from($offlinePayload);

                        if ($canBypass || ($dto->total <= getBypassLimit() && $user->hasPermission('bypass_approval_under_limit'))) {
                            if (isset($existingRequest) && $existingRequest->status == 2) {
                                // A rejected request is now being bypassed (e.g. user gained permissions)
                                $existingRequest->delete(); 
                            }
                            
                            // Direct Creation via our new decoupled Action
                            $order = CreateOrderAction::execute($dto, $user->id);
                            
                            // Attach the UUID to the final Order
                            $order->uuid = $uuid;
                            $order->save();
                            
                            $syncedIds[$uuid] = $order->id;
                            $requiresApproval[$uuid] = false;
                        } else {
                            if (isset($existingRequest) && $existingRequest->status == 2) {
                                // Resurrect the rejected request!
                                $existingRequest->update([
                                    'customer_id' => $dto->customer_id,
                                    'customer_name' => $dto->customer_name,
                                    'total_amount' => $dto->total,
                                    'payload' => $offlinePayload,
                                    'status' => 0, // Reset to Pending
                                    'rejection_reason' => null
                                ]);
                                
                                $syncedIds[$uuid] = $existingRequest->id;
                                $requiresApproval[$uuid] = true;
                                $createdRequestIds[] = $existingRequest->id;
                            } else {
                                // Manager Approval Required - Store as a Request
                                $orderRequest = OrderRequest::create([
                                    'request_number' => OrderService::generateRequestID(),
                                    'customer_id' => $dto->customer_id,
                                    'customer_name' => $dto->customer_name,
                                    'total_amount' => $dto->total,
                                    'payload' => $offlinePayload, // Keep original payload for request rendering
                                    'status' => 0, 
                                    'created_by' => $user->id,
                                    'uuid' => $uuid
                                ]);
    
                                $syncedIds[$uuid] = $orderRequest->id;
                                $requiresApproval[$uuid] = true;
                                $createdRequestIds[] = $orderRequest->id;
                            }
                        }
                    });
                } catch (\Exception $e) {
                    // Granular Error Tracking prevents one bad order from corrupting the whole sync queue
                    $failedIds[$uuid] = "Validation/Sync Error: " . $e->getMessage();
                }
            }
        });

        // 3. Batch Notifications
        // If 50 requests were made, we send ONE notification to the managers, preventing spam & timeouts.
        if (!empty($createdRequestIds)) {
            $verifiedCount = OrderRequest::whereIn('id', $createdRequestIds)->count();
            if ($verifiedCount > 0) {
                foreach($managers as $manager) {
                    $manager->notify(new SystemNotification(
                        'New Offline Order Requests',
                        "There are {$verifiedCount} new offline order request(s) requiring your approval.",
                        route('orders.requests')
                    ));
                }
            }
        }

        // 4. Batch Cache Invalidation
        // Update the Pos cache exactly ONCE after all DB writes are finished
        Cache::put('pos_last_update', time());

        // Return the exact strict format the legacy Vue PWA is expecting to gracefully clear its IndexedDB
        return [
            'synced_orders' => $syncedIds,
            'requires_approval' => $requiresApproval,
            'failed' => $failedIds
        ];
    }
}
