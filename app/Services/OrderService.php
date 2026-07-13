<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use App\Models\Payment;
use App\Models\OrderRequest;
use Carbon\Carbon;

class OrderService
{
    public static function establishOrder($payload, $userId)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($payload, $userId) {
            $order_number = self::generateOrderID();

            $order = Order::create([
                'order_number' => $order_number,
                'customer_id' => $payload['customer_id'] ?? null,
                'customer_name' => $payload['customer_name'] ?? null,
                'phone_number' => $payload['phone_number'] ?? null,
                'order_date' => Carbon::parse($payload['order_date'])->toDateTimeString(),
                'delivery_date' => Carbon::parse($payload['delivery_date'])->toDateTimeString(),
                'sub_total' => $payload['sub_total'],
                'addon_total' => $payload['addon_total'],
                'discount' => $payload['discount'] ?? 0,
                'tax_percentage' => $payload['tax_percentage'],
                'tax_amount' => $payload['tax_amount'],
                'tax_type' => $payload['tax_type'],
                'taxable_amount' => $payload['taxable_amount'],
                'total' => $payload['total'],
                'note' => $payload['note'] ?? null,
                'status' => 0,
                'order_type' => 1,
                'created_by' => $userId,
                'financial_year_id' => getFinancialYearId()
            ]);

            if (isset($payload['details'])) {
                foreach ($payload['details'] as $detail) {
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

            if (isset($payload['addons'])) {
                foreach ($payload['addons'] as $addon) {
                    OrderAddonDetail::create([
                        'order_id' => $order->id,
                        'addon_id' => $addon['addon_id'],
                        'addon_name' => $addon['addon_name'],
                        'addon_price' => $addon['addon_price'],
                    ]);
                }
            }

            if (isset($payload['payments'])) {
                foreach ($payload['payments'] as $payment) {
                    Payment::create([
                        'payment_date' => $payload['order_date'],
                        'customer_id' => $payload['customer_id'] ?? null,
                        'customer_name' => $payload['customer_name'] ?? null,
                        'order_id' => $order->id,
                        'payment_type' => $payment['payment_type'],
                        'received_amount' => $payment['amount'],
                        'notes' => $payment['notes'] ?? "Notes",
                        'financial_year_id' => getFinancialYearId(),
                        'created_by' => $userId,
                    ]);
                }
            }

            return $order;
        });
    }

    public static function generateOrderID()
    {
        $code_prefix = 'ORD-';
        
        // Lock the sequences table for order_number
        $sequence = \Illuminate\Support\Facades\DB::table('sequences')->where('name', 'order_number')->lockForUpdate()->first();
        
        $new_code = $sequence->value + 1;
        
        \Illuminate\Support\Facades\DB::table('sequences')->where('name', 'order_number')->update(['value' => $new_code]);
        
        $new_code_str = str_pad($new_code, 4, "0", STR_PAD_LEFT);
        return $code_prefix . $new_code_str;
    }

    public static function generateRequestID()
    {
        $code_prefix = 'REQ-';
        
        // Lock the sequences table for request_number
        $sequence = \Illuminate\Support\Facades\DB::table('sequences')->where('name', 'request_number')->lockForUpdate()->first();
        
        $new_code = $sequence->value + 1;
        
        \Illuminate\Support\Facades\DB::table('sequences')->where('name', 'request_number')->update(['value' => $new_code]);
        
        $new_code_str = str_pad($new_code, 4, "0", STR_PAD_LEFT);
        return $code_prefix . $new_code_str;
    }
}
