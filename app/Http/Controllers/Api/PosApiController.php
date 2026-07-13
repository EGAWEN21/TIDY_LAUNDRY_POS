<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\ServiceDetail;
use App\Models\Addon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class PosApiController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('pos-pwa')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function init()
    {
        // Serve all static data needed for offline POS
        $services = Service::where('is_active', 1)->latest()->get();
        $serviceTypes = ServiceType::orderBy('position', 'asc')->orderBy('id', 'asc')->get();
        $serviceDetails = ServiceDetail::all();
        $addons = Addon::where('is_active', 1)->latest()->get();
        $customers = Customer::where('is_active', 1)->latest()->get();
        
        return response()->json([
            'services' => $services,
            'service_types' => $serviceTypes,
            'service_details' => $serviceDetails,
            'addons' => $addons,
            'customers' => $customers,
            'settings' => [
                'tax_percentage' => getTaxPercentage(),
                'tax_type' => getTaxType(),
                'financial_year_id' => getFinancialYearId(),
                'currency' => getCurrency()
            ],
            'timestamp' => time()
        ]);
    }

    public function checkUpdates()
    {
        return response()->json([
            'timestamp' => Cache::get('pos_last_update', 0)
        ]);
    }

    public function syncCustomers(Request $request)
    {
        $customers = $request->input('customers', []);
        $syncedIds = [];
        $failedIds = [];
        
        foreach ($customers as $cust) {
            try {
                $customer = Customer::updateOrCreate(
                    ['phone' => $cust['phone']],
                    [
                        'uuid' => $cust['uuid'] ?? null,
                        'name' => $cust['name'],
                        'email' => $cust['email'] ?? null,
                        'tax_number' => $cust['tax_number'] ?? null,
                        'address' => $cust['address'] ?? null,
                        'is_active' => 1
                    ]
                );
                if(isset($cust['uuid'])) {
                    $syncedIds[$cust['uuid']] = $customer->id;
                }
            } catch (\Exception $e) {
                if(isset($cust['uuid'])) {
                    $failedIds[$cust['uuid']] = "Customer Sync Error: " . $e->getMessage();
                }
            }
        }

        return response()->json(['synced_customers' => $syncedIds, 'failed' => $failedIds]);
    }

    public function syncOrders(Request $request)
    {
        $orders = $request->input('orders', []);
        $syncedIds = [];
        $requiresApproval = [];
        $failedIds = [];

        foreach ($orders as $offlineOrder) {
            $uuid = $offlineOrder['uuid'] ?? null;
            if (!$uuid) {
                continue; // Cannot track without a UUID
            }

            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($offlineOrder, $uuid, &$syncedIds, &$requiresApproval) {
                    
                    // 1. Idempotency Check: Did we already sync this order?
                    if (Auth::user()->hasPermission('accept_reject_order')) {
                        $existingOrder = Order::where('uuid', $uuid)->first();
                        if ($existingOrder) {
                            $syncedIds[$uuid] = $existingOrder->id;
                            $requiresApproval[$uuid] = false;
                            return; // Already processed
                        }
                    } else {
                        $existingRequest = \App\Models\OrderRequest::where('uuid', $uuid)->first();
                        if ($existingRequest) {
                            $syncedIds[$uuid] = $existingRequest->id;
                            $requiresApproval[$uuid] = true;
                            return; // Already processed
                        }
                    }

                    $customerId = $offlineOrder['customer_id'] ?? null;

                    // 2. Unified Graph Sync: Process nested new customer if present
                    if (!empty($offlineOrder['new_customer'])) {
                        $custData = $offlineOrder['new_customer'];
                        if (!empty($custData['phone'])) {
                            // Atomic upsert check
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
                            $customerId = $customer->id;
                            $offlineOrder['customer_id'] = $customerId;
                            // Ensure phone_number is available for the order payload if needed
                            $offlineOrder['phone_number'] = $custData['phone'];
                        }
                    } 
                    // Fallback to searching by phone if passed directly
                    elseif (!empty($offlineOrder['phone_number'])) {
                        $dbCustomer = Customer::where('phone', $offlineOrder['phone_number'])->first();
                        if ($dbCustomer) {
                            $customerId = $dbCustomer->id;
                            $offlineOrder['customer_id'] = $customerId;
                        }
                    }

                    // 3. Process the Order
                    if (Auth::user()->hasPermission('accept_reject_order')) {
                        // User has manager privileges, bypass requests and establish order directly
                        $order = \App\Services\OrderService::establishOrder($offlineOrder, Auth::id());
                        
                        // Update UUID on the order
                        $order->uuid = $uuid;
                        $order->save();
                        
                        $syncedIds[$uuid] = $order->id;
                        $requiresApproval[$uuid] = false;

                        // Trigger automated SMS for order creation
                        sendOrderCreateSMS($order->id, $order->customer_id);

                    } else {
                        // User does not have privileges, create an OrderRequest
                        $orderRequest = \App\Models\OrderRequest::create([
                            'request_number' => \App\Services\OrderService::generateRequestID(),
                            'customer_id' => $customerId,
                            'customer_name' => $offlineOrder['customer_name'] ?? null,
                            'total_amount' => $offlineOrder['total'],
                            'payload' => $offlineOrder, // Storing the full order data here safely.
                            'status' => 0, // Pending
                            'created_by' => Auth::id(),
                            'uuid' => $uuid
                        ]);

                        // Notify managers
                        $managers = \App\Models\User::whereHas('role', function($q) {
                            $q->whereHas('permissions', function($p) {
                                $p->where('permission_name', 'accept_reject_order');
                            });
                        })->get();

                        foreach($managers as $manager) {
                            $manager->notify(new \App\Notifications\SystemNotification(
                                'New Order Request',
                                'A new order request (' . $orderRequest->request_number . ') requires your approval.',
                                route('orders.requests')
                            ));
                        }

                        $syncedIds[$uuid] = $orderRequest->id;
                        $requiresApproval[$uuid] = true;
                    }
                });
            } catch (\Exception $e) {
                // Granular Error Reporting
                $failedIds[$uuid] = "Sync Error: " . $e->getMessage();
            }
        }

        return response()->json([
            'synced_orders' => $syncedIds,
            'requires_approval' => $requiresApproval,
            'failed' => $failedIds
        ]);
    }
}
