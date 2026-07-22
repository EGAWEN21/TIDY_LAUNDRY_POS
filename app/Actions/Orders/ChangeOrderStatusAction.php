<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\Customer;
use App\Models\MasterSettings;
use App\Services\WhatsAppService;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Auth;

class ChangeOrderStatusAction
{
    /**
     * Centralized logic for changing an order's status and triggering all related
     * automations (Email, WhatsApp Burner API, WhatsApp Fallback, and SMS).
     * 
     * Returns an array with instructions for the Livewire frontend (e.g. if it needs to open a fallback URL).
     */
    public static function execute(int $orderId, int $status, bool $isBulk = false)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found.'];
        }

        $order->status = $status;
        $order->save();
        
        $response = ['success' => true, 'message' => 'Status successfully updated!'];

        // 1. Trigger Email Automation
        sendOrderStatusChangeEmail($order->id, $status);
        if (Auth::check()) {
            $statusText = getOrderStatus($status, true);
            Auth::user()->notify(new SystemNotification('Email Automation', "Automated Status Email triggered for Order {$order->order_number} ({$statusText})", 'info'));
        }

        // 2. Trigger WhatsApp Hybrid Automation
        $settings = new MasterSettings();
        $site = $settings->siteData();
        
        if (isset($site['enable_automated_whatsapp']) && $site['enable_automated_whatsapp'] == 1) {
            // Strategy 3: Burner API (Automated)
            $waService = new WhatsAppService();
            $messagePayload = getFormatedTextSMS($order->id, ($status == 2 ? 3 : 2));
            $waService->sendAutomatedStatusUpdate($order, $messagePayload); 
            if (Auth::check()) {
                Auth::user()->notify(new SystemNotification('WhatsApp Automation', "Automated WhatsApp Message sent for Order {$order->order_number} via Burner API", 'success'));
            }
        } else {
            // Strategy 1: wa.me Fallback (Manual Assist)
            if (!$isBulk) {
                $customer = Customer::find($order->customer_id);
                if ($customer && !empty($customer->phone)) {
                    $phone = ltrim($customer->phone, '+');
                    if (!str_starts_with($phone, ltrim(getCountryCode(), '+')) && strlen($phone) <= 10) {
                        $phone = ltrim(getCountryCode(), '+') . $phone;
                    }
                    $messagePayload = getFormatedTextSMS($order->id, ($status == 2 ? 3 : 2));
                    $url = "https://wa.me/{$phone}?text=" . urlencode($messagePayload);
                    
                    // Instruct frontend to open this URL
                    $response['open_url'] = $url;
                    
                    if (Auth::check()) {
                        Auth::user()->notify(new SystemNotification('WhatsApp Fallback', "Manual wa.me link generated for Order {$order->order_number}", 'warning'));
                    }
                }
            }
        }

        // 3. Trigger SMS Automation
        $smsError = sendOrderStatusChangeSMS($order->id, $status);
        if ($smsError) {
            $response['sms_error'] = $smsError;
        }

        return $response;
    }
}
