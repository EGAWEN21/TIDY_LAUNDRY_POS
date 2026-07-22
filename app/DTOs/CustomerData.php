<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Class CustomerData
 * 
 * Represents a nested offline customer payload from the Vue PWA.
 * Enables the Unified Graph Sync strategy.
 */
#[MapName(SnakeCaseMapper::class)]
class CustomerData extends Data
{
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $uuid = null,
        public ?string $email = null,
        public ?string $tax_number = null,
        public ?string $address = null,
        public int $is_active = 1,
    ) {
    }
}
