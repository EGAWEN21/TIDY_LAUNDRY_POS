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
        
        foreach ($customers as $cust) {
            // Upsert based on phone number to prevent duplicates
            $customer = Customer::updateOrCreate(
                ['phone' => $cust['phone']],
                [
                    'name' => $cust['name'],
                    'email' => $cust['email'] ?? null,
                    'tax_number' => $cust['tax_number'] ?? null,
                    'address' => $cust['address'] ?? null,
                    'is_active' => 1
                ]
            );
            // Return mapping of local UUID to server ID
            if(isset($cust['uuid'])) {
                $syncedIds[$cust['uuid']] = $customer->id;
            }
        }

        return response()->json(['synced_customers' => $syncedIds]);
    }

    public function syncOrders(Request $request)
    {
        $orders = $request->input('orders', []);
        $syncedIds = [];
        $requiresApproval = [];

        foreach ($orders as $offlineOrder) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($offlineOrder, &$syncedIds, &$requiresApproval) {
                
                // Safely resolve the real customer ID from the database using phone number
                $customerId = $offlineOrder['customer_id'] ?? null;
                if (!empty($offlineOrder['phone_number'])) {
                    $dbCustomer = Customer::where('phone', $offlineOrder['phone_number'])->first();
                    if ($dbCustomer) {
                        $customerId = $dbCustomer->id;
                        $offlineOrder['customer_id'] = $customerId;
                    }
                }

                if (Auth::user()->hasPermission('accept_reject_order')) {
                    // User has manager privileges, bypass requests and establish order directly
                    $order = \App\Services\OrderService::establishOrder($offlineOrder, Auth::id());
                    
                    if(isset($offlineOrder['uuid'])) {
                        $syncedIds[$offlineOrder['uuid']] = $order->id;
                        $requiresApproval[$offlineOrder['uuid']] = false;
                    }

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
                        'created_by' => Auth::id()
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

                    if(isset($offlineOrder['uuid'])) {
                        $syncedIds[$offlineOrder['uuid']] = $orderRequest->id; // Technically request ID, not order ID, but frontend can't print it anyway.
                        $requiresApproval[$offlineOrder['uuid']] = true;
                    }
                }
            });
        }

        return response()->json([
            'synced_orders' => $syncedIds,
            'requires_approval' => $requiresApproval
        ]);
    }

    private function generateOrderID()
    {
        $code_prefix = 'ORD-';
        $ordernumber = Order::lockForUpdate()->orderBy('id', 'desc')->first();
        if ($ordernumber && $ordernumber->order_number != "") {
            $code = explode("-", $ordernumber->order_number);
            $new_code = (int)$code[1] + 1;
            $new_code = str_pad($new_code, 4, "0", STR_PAD_LEFT);
            return $code_prefix . $new_code;
        } else {
            return $code_prefix . '0001';
        }
    }
}
