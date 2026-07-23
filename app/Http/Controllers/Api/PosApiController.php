<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\ServiceDetail;
use App\Models\Addon;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is deactivated. Please contact administrator.'], 403);
        }

        if (!$user->hasPermission('order_create')) {
            return response()->json(['message' => 'You are not authorized to access the POS.'], 403);
        }

        $user->tokens()->where('name', 'pos-pwa')->delete();
        $token = $user->createToken(
            'pos-pwa',
            ['pos:access'],
            now()->addHours(12)
        )->plainTextToken;

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
                $uuid = $cust['uuid'] ?? null;
                $phone = $cust['phone'];
                
                $customer = null;
                
                if ($uuid) {
                    $customer = Customer::where('uuid', $uuid)->first();
                }
                
                if (!$customer) {
                    $customer = Customer::where('phone', $phone)->first();
                }
                
                if ($customer) {
                    if (\Illuminate\Support\Facades\Auth::user()->hasPermission('customer_edit')) {
                        $customer->update([
                            'uuid' => $uuid ?? $customer->uuid,
                            'name' => $cust['name'],
                            'email' => $cust['email'] ?? $customer->email,
                            'tax_number' => $cust['tax_number'] ?? $customer->tax_number,
                            'address' => $cust['address'] ?? $customer->address,
                        ]);
                    } else {
                        throw new \Exception("Missing customer_edit permission. Cannot overwrite existing customer profile.");
                    }
                } else {
                    $customer = Customer::create([
                        'phone' => $phone,
                        'uuid' => $uuid,
                        'name' => $cust['name'],
                        'email' => $cust['email'] ?? null,
                        'tax_number' => $cust['tax_number'] ?? null,
                        'address' => $cust['address'] ?? null,
                        'is_active' => 1
                    ]);
                }
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

        if (count($orders) > 50) {
            return response()->json([
                'synced_orders' => [], 
                'failed' => ['bulk' => 'Payload exceeds maximum limit of 50 orders per request']
            ], 413);
        }

        // Delegate entire complex batch processing to the decoupled Action
        $response = \App\Actions\Orders\SyncOfflineOrdersAction::execute($orders, \Illuminate\Support\Facades\Auth::user());

        // Return the strictly formatted response expected by the Vue PWA's syncQueue
        return response()->json($response);
    }

    public function getRejectedOrders(Request $request)
    {
        $rejectedRequests = \App\Models\OrderRequest::where('status', 2)
            ->where('created_by', Auth::id())
            ->get();
            
        return response()->json([
            'rejected_orders' => $rejectedRequests
        ]);
    }
}
