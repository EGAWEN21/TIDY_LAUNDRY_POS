<?php

namespace App\DTOs;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Class PaymentData
 * 
 * Represents a payment payload attached to an Order.
 */
#[MapName(SnakeCaseMapper::class)]
class PaymentData extends Data
{
    public function __construct(
        public int $payment_type,
        public float $amount,
        public ?string $payment_type_name = null,
        public ?string $notes = null,
        public ?int $payment_id = null
    ) {
    }
}
