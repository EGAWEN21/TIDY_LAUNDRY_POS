<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class MergeOrdersAction
{
    /**
     * Merges secondary orders into a primary order.
     * 
     * @param int $primaryOrderId
     * @param array $secondaryOrderIds
     * @return void
     * @throws \Exception
     */
    public static function execute(int $primaryOrderId, array $secondaryOrderIds, ?int $userId = null): void
    {
        if (empty($secondaryOrderIds)) {
            return;
        }

        DB::beginTransaction();
        try {
            $primaryOrder = Order::with(['details', 'addons', 'payments'])->findOrFail($primaryOrderId);
            $secondaryOrders = Order::with(['details', 'addons', 'payments'])->whereIn('id', $secondaryOrderIds)->get();

            $totalDiscountToAdd = 0;

            foreach ($secondaryOrders as $secondaryOrder) {
                if ($secondaryOrder->customer_id !== $primaryOrder->customer_id) {
                    throw new \Exception("Cannot merge orders belonging to different customers.");
                }

                // 1. Move details
                OrderDetail::where('order_id', $secondaryOrder->id)->update(['order_id' => $primaryOrder->id]);
                
                // 2. Move addons
                if (class_exists(OrderAddonDetail::class)) {
                    OrderAddonDetail::where('order_id', $secondaryOrder->id)->update(['order_id' => $primaryOrder->id]);
                }
                
                // 3. Move payments
                Payment::where('order_id', $secondaryOrder->id)->update(['order_id' => $primaryOrder->id]);

                $totalDiscountToAdd += (float)$secondaryOrder->discount;

                // 4. Delete the secondary order (Hard delete to prevent ghost orders)
                // We do not need to track deleted_by since it's fully purged.
                $secondaryOrder->forceDelete();
            }

            // Refresh primary order relationships
            $primaryOrder->refresh();

            // Prepare items for recalculation
            $mappedItems = $primaryOrder->details->map(function ($detail) {
                return [
                    'price' => (float)$detail->service_price,
                    'quantity' => (float)$detail->service_quantity,
                ];
            })->toArray();

            $addonTotal = 0;
            if ($primaryOrder->addons) {
                $addonTotal = $primaryOrder->addons->sum('addon_price');
            }
            
            $newDiscount = (float)$primaryOrder->discount + $totalDiscountToAdd;

            // Recalculate totals
            $totals = CalculateCartTotals::execute($mappedItems, (float)$addonTotal, (float)$newDiscount);

            // Update primary order
            $primaryOrder->update([
                'sub_total' => $totals['sub_total'],
                'addon_total' => $totals['addon_total'],
                'discount' => $totals['discount'],
                'tax_percentage' => $totals['tax_percentage'],
                'tax_amount' => $totals['tax_amount'],
                'tax_type' => $totals['tax_type'],
                'taxable_amount' => $totals['taxable_amount'],
                'total' => $totals['total'],
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
