<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterSettings;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function handleVerify(Request $request)
    {
        $settings = new MasterSettings();
        $site = $settings->siteData();
        $verifyToken = (isset($site['whatsapp_webhook_verify_token']) && !empty($site['whatsapp_webhook_verify_token'])) ? $site['whatsapp_webhook_verify_token'] : '';

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $verifyToken) {
                return response($challenge, 200);
            }
        }
        return response('Forbidden', 403);
    }

    public function handleMessage(Request $request, WhatsAppService $whatsAppService)
    {
        try {
            $entry = $request->input('entry.0');
            $changes = $entry['changes'][0]['value'] ?? null;

            if ($changes && isset($changes['messages'][0])) {
                $message = $changes['messages'][0];
                $senderPhone = $message['from']; // The phone number that sent the message

                if ($message['type'] === 'text') {
                    $textBody = trim($message['text']['body']);
                    
                    // Fuzzy search for the order number
                    // Remove '#', 'ORD-', and spaces, then look for it.
                    // E.g., "#ORD-000001", "000001", "ORD 000001"
                    $cleanText = preg_replace('/[^a-zA-Z0-9]/', '', $textBody);
                    $strippedText = str_ireplace(['ORD-', 'ORD', '#'], '', $cleanText);
                    $strippedText = trim($strippedText);
                    
                    $order = null;
                    if (strlen($strippedText) >= 4) {
                        // Try to match exact first
                        $order = Order::with('details.service')->where('order_number', $textBody)->first();
                        
                        if (!$order) {
                            $order = Order::with('details.service')->where('order_number', 'LIKE', '%' . $strippedText . '%')->first();
                        }
                    }

                    // Send the reply (passing $order=null sends the friendly Not Found message)
                    $whatsAppService->sendReply($senderPhone, $order);
                }
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
