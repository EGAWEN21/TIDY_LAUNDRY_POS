<?php

namespace App\Actions\Orders;

use App\DTOs\CartItemData;
use App\Models\MasterSettings;
use Illuminate\Support\Collection;

/**
 * Class CalculateCartTotals
 * 
 * Handles the strict mathematical computation of cart totals and taxes.
 * This class ensures 100% parity between the Offline Vue PWA math and the Online Livewire math.
 */
class CalculateCartTotals
{
    /**
     * Execute the mathematical calculation.
     * 
     * @param Collection<int, CartItemData>|array $cartItems
     * @param float $addonTotal
     * @param float $discount
     * @return array
     */
    public static function execute($cartItems, float $addonTotal = 0, float $discount = 0): array
    {
        $settings = (new MasterSettings())->siteData();
        $taxPercentage = (float) ($settings['default_tax_percentage'] ?? 0);
        $taxType = (int) ($settings['default_tax_mode'] ?? 1); // 1 = Exclusive, 2 = Inclusive

        // Calculate SubTotal strictly from the DTOs
        $subTotal = 0;
        foreach ($cartItems as $item) {
            // Force strict casting in case of malformed data
            if ($item instanceof CartItemData) {
                $subTotal += ($item->service_price * $item->service_quantity);
            } else {
                // Legacy array fallback during migration
                $subTotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            }
        }

        $grossTotal = $subTotal + $addonTotal;
        $taxAmount = 0;

        // Calculate Tax exactly as Vue PWA does
        if ($taxType === 2) {
            // Tax Inclusive: The gross price already includes the tax
            $taxFree = $grossTotal * (100 / (100 + $taxPercentage));
            $taxAmount = $grossTotal - $taxFree;
            $taxableAmount = $taxFree;
            $finalTotal = $grossTotal - $discount;
        } else {
            // Tax Exclusive: Tax is added on top of the gross price
            $taxAmount = $grossTotal * ($taxPercentage / 100);
            $taxableAmount = $grossTotal;
            $finalTotal = ($grossTotal + $taxAmount) - $discount;
        }

        // Ensure final total never goes below zero
        $finalTotal = max(0, $finalTotal);

        return [
            'sub_total' => round($subTotal, 2),
            'addon_total' => round($addonTotal, 2),
            'discount' => round($discount, 2),
            'tax_percentage' => round($taxPercentage, 2),
            'tax_amount' => round($taxAmount, 2),
            'tax_type' => $taxType,
            'taxable_amount' => round($taxableAmount, 2),
            'total' => round($finalTotal, 2)
        ];
    }
}
