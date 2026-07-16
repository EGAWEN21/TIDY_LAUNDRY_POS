<?php

namespace App\Listeners;

use App\Events\OrderSuccessfullyCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Class SendOrderNotifications
 * 
 * Asynchronously handles third-party HTTP requests (SMS/WhatsApp)
 * after an order is securely committed. Implements ShouldQueue to
 * prevent the main UI/API thread from hanging.
 */
class SendOrderNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(OrderSuccessfullyCreated $event): void
    {
        try {
            // Legacy SMS Function (from app/Helpers/Helper.php)
            if (function_exists('sendOrderCreateSMS')) {
                sendOrderCreateSMS($event->order->id, $event->customerId);
            }

            // Future-proofing WhatsApp Automated Status
            // $whatsAppService = app(\App\Services\WhatsAppService::class);
            // $whatsAppService->sendAutomatedStatusUpdate($event->order, "Your order {$event->order->order_number} has been placed successfully!");
            
        } catch (\Exception $e) {
            // Log the error but DO NOT throw it, so we don't break the queue or rollback anything
            Log::error('Order Notification Failed: ' . $e->getMessage(), [
                'order_id' => $event->order->id,
            ]);
        }
    }
}
