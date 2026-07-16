<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

/**
 * Class CartItemData
 * 
 * Represents a single item inside an Order's shopping cart.
 * Strictly types the payload sent from the Vue PWA or Livewire component.
 */
#[MapName(SnakeCaseMapper::class)]
class CartItemData extends Data
{
    public function __construct(
        public int $service_id,
        public float $service_price,
        public int $service_quantity,
        
        // PWA sends 'service_detail_total', which is (price * quantity)
        // We capture it but will recalculate it on the server to prevent manipulation.
        public float $service_detail_total, 
        
        // PWA sends 'service_name' but it maps to the service_type_name
        #[MapInputName('service_name')]
        public ?string $service_name = null,
        
        public ?string $color_code = null,
    ) {
    }
}
