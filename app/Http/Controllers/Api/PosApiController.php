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
            ]
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

        foreach ($orders as $offlineOrder) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($offlineOrder, &$syncedIds) {
                $order_number = $this->generateOrderID();

                // Safely resolve the real customer ID from the database using phone number
                $customerId = $offlineOrder['customer_id'] ?? null;
                if (!empty($offlineOrder['phone_number'])) {
                    $dbCustomer = Customer::where('phone', $offlineOrder['phone_number'])->first();
                    if ($dbCustomer) {
                        $customerId = $dbCustomer->id;
                    }
                }

                $order = Order::create([
                    'order_number' => $order_number,
                    'customer_id' => $customerId,
                    'customer_name' => $offlineOrder['customer_name'] ?? null,
                    'phone_number' => $offlineOrder['phone_number'] ?? null,
                    'order_date' => $offlineOrder['order_date'],
                    'delivery_date' => $offlineOrder['delivery_date'],
                    'sub_total' => $offlineOrder['sub_total'],
                    'addon_total' => $offlineOrder['addon_total'],
                    'discount' => $offlineOrder['discount'] ?? 0,
                    'tax_percentage' => $offlineOrder['tax_percentage'],
                    'tax_amount' => $offlineOrder['tax_amount'],
                    'tax_type' => $offlineOrder['tax_type'],
                    'taxable_amount' => $offlineOrder['taxable_amount'],
                    'total' => $offlineOrder['total'],
                    'note' => $offlineOrder['note'] ?? null,
                    'status' => 0,
                    'order_type' => 1,
                    'created_by' => Auth::id(),
                    'financial_year_id' => getFinancialYearId()
                ]);

                if (isset($offlineOrder['details'])) {
                    foreach ($offlineOrder['details'] as $detail) {
                        OrderDetail::create([
                            'order_id' => $order->id,
                            'service_id' => $detail['service_id'],
                            'service_name' => $detail['service_name'],
                            'service_quantity' => $detail['service_quantity'],
                            'service_detail_total' => $detail['service_detail_total'],
                            'service_price' => $detail['service_price'],
                            'color_code' => $detail['color_code'] ?? null,
                        ]);
                    }
                }

                if (isset($offlineOrder['addons'])) {
                    foreach ($offlineOrder['addons'] as $addon) {
                        OrderAddonDetail::create([
                            'order_id' => $order->id,
                            'addon_id' => $addon['addon_id'],
                            'addon_name' => $addon['addon_name'],
                            'addon_price' => $addon['addon_price'],
                        ]);
                    }
                }

                if (isset($offlineOrder['payments'])) {
                    foreach ($offlineOrder['payments'] as $payment) {
                        Payment::create([
                            'payment_date' => $offlineOrder['order_date'],
                            'customer_id' => $customerId,
                            'customer_name' => $offlineOrder['customer_name'] ?? null,
                            'order_id' => $order->id,
                            'payment_type' => $payment['payment_type'],
                            'received_amount' => $payment['amount'],
                            'notes' => $payment['notes'] ?? "Notes",
                            'financial_year_id' => getFinancialYearId(),
                            'created_by' => Auth::id(),
                        ]);
                    }
                }

                if(isset($offlineOrder['uuid'])) {
                    $syncedIds[$offlineOrder['uuid']] = $order->id;
                }
            });
        }

        return response()->json(['synced_orders' => $syncedIds]);
    }

    private function generateOrderID()
    {
        $code_prefix = 'ORD-';
        $ordernumber = Order::Orderby('id', 'desc')->first();
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
