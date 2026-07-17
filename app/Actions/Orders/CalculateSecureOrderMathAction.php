<?php

namespace App\Actions\Orders;

use App\DTOs\OrderData;
use App\Models\User;
use App\Models\ServiceDetail;
use App\Models\Addon;
use Illuminate\Support\Facades\DB;

class CalculateSecureOrderMathAction
{
    public static function execute(OrderData $dto, User $user): OrderData
    {
        $canOverridePrice = $user->hasPermission('order_price_override');
        $canApplyDiscount = $user->hasPermission('order_discount_apply');

        if (!$canApplyDiscount) {
            $dto->discount = 0;
        }

        // 1. Verify and reconstruct Cart Items
        $secureCartItems = [];
        foreach ($dto->details as $item) {
            if ($item->service_quantity <= 0) {
                throw new \Exception("Invalid quantity for item: {$item->service_name}");
            }
            
            if (!$canOverridePrice) {
                // Fetch official price from database joining ServiceTypes to match the payload structure
                $officialPrice = DB::table('service_details')
                    ->join('service_types', 'service_details.service_type_id', '=', 'service_types.id')
                    ->where('service_details.service_id', $item->service_id)
                    ->where('service_types.service_type_name', $item->service_name)
                    ->value('service_details.service_price');
                
                if ($officialPrice !== null) {
                    $item->service_price = (float) $officialPrice;
                } else {
                    throw new \Exception("Invalid service details provided for item: {$item->service_name}");
                }
            }
            
            if ($item->service_price < 0) {
                throw new \Exception("Negative prices are not allowed for item: {$item->service_name}");
            }

            $item->service_detail_total = $item->service_price * $item->service_quantity;
            $secureCartItems[] = $item;
        }

        // 2. Verify and reconstruct Addons
        $secureAddonTotal = 0;
        if ($dto->addons) {
            foreach ($dto->addons as $addon) {
                if (!$canOverridePrice) {
                    $officialAddonPrice = Addon::where('id', $addon->addon_id)->value('addon_price');
                    if ($officialAddonPrice !== null) {
                        $addon->addon_price = (float) $officialAddonPrice;
                    } else {
                        throw new \Exception("Invalid addon provided: ID {$addon->addon_id}");
                    }
                }
                
                if ($addon->addon_price < 0) {
                    throw new \Exception("Negative prices are not allowed for addon: {$addon->addon_name}");
                }
                
                $secureAddonTotal += $addon->addon_price;
            }
        }
        $dto->addon_total = $secureAddonTotal;

        // 3. Delegate to the mathematical engine
        $totals = CalculateCartTotals::execute(
            cartItems: $secureCartItems,
            addonTotal: $dto->addon_total,
            discount: $dto->discount
        );

        // 4. Overwrite DTO with securely calculated totals
        $dto->sub_total = $totals['sub_total'];
        $dto->addon_total = $totals['addon_total'];
        $dto->discount = $totals['discount'];
        $dto->tax_percentage = $totals['tax_percentage'];
        $dto->tax_amount = $totals['tax_amount'];
        $dto->tax_type = $totals['tax_type'];
        $dto->taxable_amount = $totals['taxable_amount'];
        $dto->total = $totals['total'];

        return $dto;
    }
}
