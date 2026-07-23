<?php

namespace App\Actions\Orders;

use App\DTOs\OrderData;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class UpdateOrderAction
 * 
 * Securely updates an existing order in the database using strict DTO contracts.
 * Synchronizes the ledger by matching the UI state.
 */
class UpdateOrderAction
{
    /**
     * Execute the secure update of an order.
     */
    public static function execute(OrderData $dto, Order $order, int $userId): Order
    {
        return DB::transaction(function () use ($dto, $order, $userId) {
            
            $order->update([
                'customer_id' => $dto->customer_id,
                'customer_name' => $dto->customer_name,
                'phone_number' => $dto->phone_number,
                'order_date' => Carbon::parse($dto->order_date)->toDateTimeString(),
                'delivery_date' => Carbon::parse($dto->delivery_date)->toDateTimeString(),
                'sub_total' => $dto->sub_total,
                'addon_total' => $dto->addon_total,
                'discount' => $dto->discount,
                'tax_percentage' => $dto->tax_percentage,
                'tax_amount' => $dto->tax_amount,
                'tax_type' => $dto->tax_type,
                'taxable_amount' => $dto->taxable_amount,
                'total' => $dto->total,
                'note' => $dto->note ?? $order->note,
            ]);

            OrderDetail::whereOrderId($order->id)->forceDelete();
            OrderAddonDetail::whereOrderId($order->id)->forceDelete();
            // We no longer delete existing payments to preserve the ledger history.

            foreach ($dto->details as $detail) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'service_id' => $detail->service_id,
                    'service_name' => $detail->service_name,
                    'service_quantity' => $detail->service_quantity,
                    'service_detail_total' => $detail->service_detail_total,
                    'service_price' => $detail->service_price,
                    'color_code' => $detail->color_code,
                ]);
            }

            if ($dto->addons) {
                foreach ($dto->addons as $addon) {
                    \App\Models\OrderAddonDetail::create([
                        'order_id' => $order->id,
                        'addon_id' => $addon->addon_id,
                        'addon_name' => $addon->addon_name,
                        'addon_price' => $addon->addon_price,
                    ]);
                }
            }

            foreach ($dto->payments as $payment) {
                if (empty($payment->payment_id)) {
                    if ($payment->amount < 0) {
                        throw new \InvalidArgumentException('Negative payment amounts are not allowed when updating an order.');
                    }

                    Payment::create([
                        'payment_date' => $dto->order_date,
                        'customer_id' => $dto->customer_id,
                        'customer_name' => $dto->customer_name,
                        'order_id' => $order->id,
                        'payment_type' => $payment->payment_type,
                        'received_amount' => $payment->amount,
                        'payment_note' => $payment->notes ?? null,
                        'financial_year_id' => resolveFinancialYearId($dto->order_date),
                        'created_by' => $userId,
                    ]);
                }
            }

            return $order;
        });
    }
}
