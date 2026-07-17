<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Attributes\MapName;

/**
 * Class AddonData
 */
#[MapName(SnakeCaseMapper::class)]
class AddonData extends Data
{
    public function __construct(
        public int $addon_id,
        public ?string $addon_name = null,
        public ?float $addon_price = null,
    ) {
    }
}
