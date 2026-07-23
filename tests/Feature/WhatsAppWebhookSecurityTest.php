<?php

namespace Tests\Feature;

use Tests\TestCase;

class WhatsAppWebhookSecurityTest extends TestCase
{
    public function test_it_rejects_webhooks_when_the_app_secret_is_not_configured(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        $this->call('POST', '/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], '{}')->assertForbidden();
    }

    public function test_it_rejects_webhooks_with_an_invalid_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'test-secret']);

        $this->call('POST', '/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], '{}')->assertForbidden();
    }

    public function test_it_accepts_a_valid_signature_for_the_exact_raw_request_body(): void
    {
        $secret = 'test-secret';
        $body = '{"object":"whatsapp_business_account","entry":[]}';
        config(['services.whatsapp.app_secret' => $secret]);

        $this->call('POST', '/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, $secret),
        ], $body)
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }
}
