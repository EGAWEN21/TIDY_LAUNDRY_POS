<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class OrderSuccessfullyCreated
 * 
 * Fired immediately after an order is securely committed to the database.
 * Used to decouple slow, third-party HTTP requests (like SMS and WhatsApp)
 * from the main database transaction to prevent catastrophic rollbacks.
 */
class OrderSuccessfullyCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public int $customerId;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, int $customerId)
    {
        $this->order = $order;
        $this->customerId = $customerId;
    }
}
