<?php

namespace App\Services;

use App\Models\MasterSettings;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected $enabled;
    protected $apiUrl;
    protected $accessToken;
    protected $phoneNumberId;
    protected $notFoundMessage;

    public function __construct()
    {
        $settings = new MasterSettings();
        $site = $settings->siteData();

        $this->enabled = (isset($site['whatsapp_enabled']) && !empty($site['whatsapp_enabled'])) ? $site['whatsapp_enabled'] : 0;
        $this->apiUrl = (isset($site['whatsapp_api_url']) && !empty($site['whatsapp_api_url'])) ? rtrim($site['whatsapp_api_url'], '/') : 'https://graph.facebook.com/v18.0';
        $this->accessToken = (isset($site['whatsapp_access_token']) && !empty($site['whatsapp_access_token'])) ? $site['whatsapp_access_token'] : '';
        $this->phoneNumberId = (isset($site['whatsapp_phone_number_id']) && !empty($site['whatsapp_phone_number_id'])) ? $site['whatsapp_phone_number_id'] : '';
        $this->notFoundMessage = (isset($site['whatsapp_not_found_message']) && !empty($site['whatsapp_not_found_message'])) ? $site['whatsapp_not_found_message'] : 'Order not found.';
        
        $businessNumber = (isset($site['whatsapp_business_number']) && !empty($site['whatsapp_business_number'])) ? $site['whatsapp_business_number'] : '';
        $this->notFoundMessage = str_replace('<support_number>', $businessNumber, $this->notFoundMessage);
    }

    public function isEnabled()
    {
        return $this->enabled == 1;
    }

    public function sendReply($toPhoneNumber, Order $order = null)
    {
        if (!$this->isEnabled() || empty($this->accessToken) || empty($this->phoneNumberId)) {
            return false;
        }

        $messageText = $order ? $this->formatOrderMessage($order) : $this->notFoundMessage;

        return $this->sendMessagePayload($toPhoneNumber, $messageText);
    }

    protected function formatOrderMessage(Order $order)
    {
        $orderDate = \Carbon\Carbon::parse($order->order_date)->format('d M Y');
        $deliveryDate = \Carbon\Carbon::parse($order->delivery_date)->format('d M Y');
        $status = getOrderStatus($order->status, true);
        $total = getFormattedCurrency($order->total);
        $paid = Payment::where('order_id', $order->id)->sum('received_amount');
        
        $paymentStatus = ($paid >= $order->total) ? 'Paid' : 'Unpaid (Remaining: ' . getFormattedCurrency($order->total - $paid) . ')';

        $itemsList = "";
        if ($order->details) {
            foreach ($order->details as $detail) {
                $serviceName = $detail->service ? $detail->service->service_title : 'Item';
                $itemsList .= "- {$detail->service_quantity}x {$serviceName}\n";
            }
        }

        $message = "*Order Details for {$order->customer_name}*\n";
        $message .= "Order Number: {$order->order_number}\n";
        $message .= "Order Date: {$orderDate}\n";
        $message .= "Expected Date: {$deliveryDate}\n\n";
        
        $message .= "*Status:* {$status}\n\n";
        
        $message .= "*Items:*\n{$itemsList}\n";
        
        $message .= "*Payment Status:* {$paymentStatus}\n";
        $message .= "*Total Amount:* {$total}";

        return $message;
    }

    protected function sendMessagePayload($to, $text)
    {
        $endpoint = "{$this->apiUrl}/{$this->phoneNumberId}/messages";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($endpoint, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text
            ]
        ]);

        return $response->successful();
    }
}
