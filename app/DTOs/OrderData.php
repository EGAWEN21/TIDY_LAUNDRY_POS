<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Class OrderData
 * 
 * The master Data Transfer Object for creating and syncing orders.
 * This class maps exactly to the legacy Vue 3 PWA `buildOrderData()` JSON structure 
 * to ensure offline synchronization does not fail due to validation errors.
 */
#[MapName(SnakeCaseMapper::class)]
class OrderData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?int $customer_id = null,
        public ?string $customer_name = null,
        public ?string $phone_number = null,
        
        public ?CustomerData $new_customer = null,
        
        public string $order_date,
        public string $delivery_date,
        
        public float $sub_total,
        public float $addon_total,
        public float $discount,
        public float $tax_percentage,
        public float $tax_amount,
        public int $tax_type,
        public float $taxable_amount,
        public float $total,
        public int $status = 0,
        
        #[DataCollectionOf(CartItemData::class)]
        public DataCollection $details,
        
        #[DataCollectionOf(AddonData::class)]
        public ?DataCollection $addons,
        
        #[DataCollectionOf(PaymentData::class)]
        public DataCollection $payments,
    ) {
    }
}
