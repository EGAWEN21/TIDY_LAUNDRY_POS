<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SplitOrderAction
{
    /**
     * Splits specific line items from an original order into a new order,
     * and manually allocates existing payments between them.
     * 
     * @param int $originalOrderId
     * @param array $orderDetailIdsToSplit
     * @param array $paymentAllocations ['original' => float, 'new' => float]
     * @param int $userId The ID of the user performing the split
     * @return Order The newly created order
     * @throws \Exception
     */
    public static function execute(int $originalOrderId, array $orderDetailIdsToSplit, array $paymentAllocations, int $userId): Order
    {
        if (empty($orderDetailIdsToSplit)) {
            throw new \Exception("No items selected to split.");
        }

        DB::beginTransaction();
        try {
            $originalOrder = Order::with(['details', 'addons', 'payments'])->findOrFail($originalOrderId);

            // 1. Verify items belong to original order
            $detailsToSplit = OrderDetail::whereIn('id', $orderDetailIdsToSplit)->get();
            foreach ($detailsToSplit as $detail) {
                if ($detail->order_id !== $originalOrder->id) {
                    throw new \Exception("Line item #{$detail->id} does not belong to the selected order.");
                }
            }

            if ($detailsToSplit->count() === $originalOrder->details->count()) {
                throw new \Exception("Cannot split all items. Leave at least one item on the original order.");
            }

            // 2. Validate Payments
            $totalExistingPayments = $originalOrder->payments->sum('received_amount');
            $allocatedOriginal = (float)($paymentAllocations['original'] ?? 0);
            $allocatedNew = (float)($paymentAllocations['new'] ?? 0);

            if ($allocatedOriginal < 0 || $allocatedNew < 0) {
                throw new \Exception("Payment allocations cannot be negative.");
            }

            // Allow a tiny float margin for currency issues, but it should match exactly
            if (round($allocatedOriginal + $allocatedNew, 2) !== round($totalExistingPayments, 2)) {
                throw new \Exception("The sum of allocated payments (" . ($allocatedOriginal + $allocatedNew) . ") must exactly match the total existing payments ({$totalExistingPayments}).");
            }

            // 3. Create New Order
            $newOrder = Order::create([
                'order_number' => self::generateUniqueOrderNumber(),
                'customer_id' => $originalOrder->customer_id,
                'customer_name' => $originalOrder->customer_name,
                'phone_number' => $originalOrder->phone_number,
                'order_date' => $originalOrder->order_date,
                'delivery_date' => $originalOrder->delivery_date,
                'sub_total' => 0,
                'addon_total' => 0,
                'discount' => 0, // Keep discounts on original order for simplicity
                'tax_percentage' => $originalOrder->tax_percentage,
                'tax_amount' => 0,
                'tax_type' => $originalOrder->tax_type,
                'taxable_amount' => 0,
                'total' => 0,
                'note' => 'Split from ' . $originalOrder->order_number . '. ' . $originalOrder->note,
                'status' => $originalOrder->status,
                'order_type' => $originalOrder->order_type,
                'created_by' => $userId,
                'financial_year_id' => $originalOrder->financial_year_id,
                'uuid' => (string) Str::uuid(),
            ]);

            // 4. Move selected items
            OrderDetail::whereIn('id', $orderDetailIdsToSplit)->update(['order_id' => $newOrder->id]);

            // Refresh relationships
            $originalOrder->refresh();
            $newOrder->refresh();

            // 5. Recalculate original order
            $originalItems = $originalOrder->details->map(fn($d) => ['price' => (float)$d->service_price, 'quantity' => (float)$d->service_quantity])->toArray();
            $originalAddons = $originalOrder->addons ? $originalOrder->addons->sum('addon_price') : 0;
            $originalTotals = CalculateCartTotals::execute($originalItems, (float)$originalAddons, (float)($originalOrder->discount ?? 0));
            
            $originalOrder->update([
                'sub_total' => $originalTotals['sub_total'],
                'addon_total' => $originalTotals['addon_total'],
                'tax_amount' => $originalTotals['tax_amount'],
                'taxable_amount' => $originalTotals['taxable_amount'],
                'total' => $originalTotals['total'],
            ]);

            // 6. Recalculate new order
            $newItems = $newOrder->details->map(fn($d) => ['price' => (float)$d->service_price, 'quantity' => (float)$d->service_quantity])->toArray();
            $newTotals = CalculateCartTotals::execute($newItems, 0, 0); // No addons or discount for split order
            
            $newOrder->update([
                'sub_total' => $newTotals['sub_total'],
                'addon_total' => $newTotals['addon_total'],
                'tax_amount' => $newTotals['tax_amount'],
                'taxable_amount' => $newTotals['taxable_amount'],
                'total' => $newTotals['total'],
            ]);

            // 7. Prevent Overpayments based on new totals
            if ($allocatedOriginal > $originalTotals['total']) {
                throw new \Exception("Allocated payment for original order ({$allocatedOriginal}) exceeds its new total cost ({$originalTotals['total']}).");
            }
            if ($allocatedNew > $newTotals['total']) {
                throw new \Exception("Allocated payment for new order ({$allocatedNew}) exceeds its new total cost ({$newTotals['total']}).");
            }

            // 8. Re-assign payments
            if ($totalExistingPayments > 0) {
                $remainingForNew = $allocatedNew;
                
                foreach ($originalOrder->payments as $payment) {
                    if ($remainingForNew <= 0) break;

                    if ($payment->received_amount <= $remainingForNew) {
                        // Move this entire payment to the new order
                        $payment->update([
                            'order_id' => $newOrder->id,
                            'customer_id' => $newOrder->customer_id,
                            'customer_name' => $newOrder->customer_name,
                            'financial_year_id' => $newOrder->financial_year_id
                        ]);
                        $remainingForNew -= $payment->received_amount;
                    } else {
                        // Split this payment
                        $amountForNew = $remainingForNew;
                        $amountForOriginal = $payment->received_amount - $remainingForNew;
                        
                        $payment->update(['received_amount' => $amountForOriginal]);
                        
                        // Create new payment for new order with same type
                        Payment::create([
                            'payment_date' => $payment->payment_date,
                            'customer_id' => $newOrder->customer_id,
                            'customer_name' => $newOrder->customer_name,
                            'order_id' => $newOrder->id,
                            'received_amount' => $amountForNew,
                            'payment_type' => $payment->payment_type,
                            'payment_note' => $payment->payment_note . ' (Allocated from split)',
                            'financial_year_id' => $newOrder->financial_year_id,
                            'created_by' => $userId,
                        ]);
                        
                        $remainingForNew = 0;
                    }
                }
            }

            DB::commit();
            return $newOrder;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private static function generateUniqueOrderNumber()
    {
        $lastOrder = Order::orderBy('id', 'desc')->first();
        if ($lastOrder) {
            $number = (int) str_replace('ORD-', '', $lastOrder->order_number);
            return 'ORD-' . str_pad($number + 1, 6, '0', STR_PAD_LEFT);
        }
        return 'ORD-000001';
    }
}
