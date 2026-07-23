<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterSettings;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

        if ($mode && $token && $verifyToken !== '') {
            if ($mode === 'subscribe' && hash_equals($verifyToken, (string) $token)) {
                return response($challenge, 200);
            }
        }
        return response('Forbidden', 403);
    }

    public function handleMessage(Request $request, WhatsAppService $whatsAppService)
    {
        $appSecret = (string) config('services.whatsapp.app_secret');
        $signature = (string) $request->header('X-Hub-Signature-256');
        $expectedSignature = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        if ($appSecret === '' || $signature === '' || !hash_equals($expectedSignature, $signature)) {
            Log::warning('Rejected WhatsApp webhook with an invalid signature.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'forbidden'], 403);
        }

        try {
            $entry = $request->input('entry.0');
            $changes = $entry['changes'][0]['value'] ?? null;

            if ($changes && isset($changes['messages'][0])) {
                $message = $changes['messages'][0];
                $messageId = $message['id'] ?? null;
                $senderPhone = $message['from'] ?? null;
                if (!$senderPhone) {
                    return response()->json(['status' => 'success'], 200);
                }

                // Cache-based Idempotency (prevent multiple processing of the same Webhook trigger)
                if ($messageId) {
                    if (Cache::has("whatsapp_msg_{$messageId}")) {
                        return response()->json(['status' => 'success'], 200);
                    }
                    Cache::put("whatsapp_msg_{$messageId}", true, now()->addMinutes(10));
                }

                if (($message['type'] ?? null) === 'text') {
                    $textBody = trim((string) ($message['text']['body'] ?? ''));
                    if ($textBody === '' || strlen($textBody) > 500) {
                        return response()->json(['status' => 'success'], 200);
                    }
                    
                    // Split text: e.g. "ORD-000012 08012345678"
                    $parts = explode(' ', $textBody);
                    $orderPart = array_shift($parts);
                    $phonePart = implode('', $parts); // Any remaining text is the challenge response

                    // Normalize order number (Exact formatting reconstruction)
                    $cleanOrder = preg_replace('/[^0-9]/', '', $orderPart);
                    $order = null;
                    
                    if (!empty($cleanOrder)) {
                        $formattedOrderNumber = "ORD-" . str_pad($cleanOrder, 6, "0", STR_PAD_LEFT);
                        $order = Order::with('details.service', 'customer')->where('order_number', $formattedOrderNumber)->first();
                    }

                    if ($order && $order->customer) {
                        $customerPhone = preg_replace('/[^0-9]/', '', $order->customer->phone);
                        $senderPhoneClean = preg_replace('/[^0-9]/', '', $senderPhone);
                        $secondaryPhoneClean = preg_replace('/[^0-9]/', '', $phonePart);

                        $isAuthorized = false;

                        // Match by last 10 digits to safely ignore country codes
                        $customerLast10 = substr($customerPhone, -10);
                        if (strlen($customerLast10) === 10 && substr($senderPhoneClean, -10) === $customerLast10) {
                            $isAuthorized = true;
                        }

                        // Secondary Verification Challenge check
                        if (!$isAuthorized && strlen($secondaryPhoneClean) >= 10 && strlen($customerLast10) === 10) {
                            if (hash_equals($customerLast10, substr($secondaryPhoneClean, -10))) {
                                $isAuthorized = true;
                            }
                        }

                        if ($isAuthorized) {
                            $whatsAppService->sendReply($senderPhone, $order);
                        } else {
                            // Dispatch Secondary Verification Challenge
                            $whatsAppService->sendMessagePayload(
                                $senderPhone, 
                                "To protect your privacy, please reply with your order number followed by your registered phone number."
                            );
                        }
                    } else {
                        // Send friendly Not Found message
                        $whatsAppService->sendReply($senderPhone, null);
                    }
                }
            }

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error'], 500);
        }
    }
}
