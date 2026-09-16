<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_service_worker_is_served_from_the_public_build_output(): void
    {
        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript')
            ->assertHeader('Service-Worker-Allowed', '/');
    }

    public function test_the_offline_pos_uses_the_automatic_service_worker_lifecycle(): void
    {
        $viteConfig = file_get_contents(base_path('vite.config.js'));
        $posSource = file_get_contents(resource_path('js/pos-app.js'));
        $serviceWorker = file_get_contents(public_path('sw.js'));
        $navigationFallback = file_get_contents(public_path('sw-fallback.js'));
        $connectivityComponent = file_get_contents(resource_path('views/components/pwa-connectivity.blade.php'));

        $this->assertStringContainsString("buildBase: '/'", $viteConfig);
        $this->assertStringContainsString("registerType: 'autoUpdate'", $viteConfig);
        $this->assertStringContainsString('injectRegister: false', $viteConfig);
        $this->assertStringNotContainsString('pwa-update-ready', $posSource);
        $this->assertStringNotContainsString('pwa-apply-update', $posSource);

        $this->assertStringContainsString("register('/sw.js', { scope: '/' })", $connectivityComponent);
        $this->assertStringNotContainsString("register('/build/sw.js'", $connectivityComponent);
        $this->assertStringContainsString('window.location.reload()', $connectivityComponent);

        $this->assertStringContainsString('skipWaiting', $serviceWorker);
        $this->assertStringContainsString('offline.html', $serviceWorker);
        $this->assertStringContainsString('pos-html-cache', $navigationFallback);
        $this->assertStringContainsString('CACHE_POS_SHELL', $navigationFallback);
        $this->assertStringContainsString('POS_SHELL_STATUS', $navigationFallback);
        $this->assertStringContainsString('CHECK_POS_SHELL', $connectivityComponent);
        $this->assertStringContainsString('new MessageChannel()', $connectivityComponent);
        $this->assertStringContainsString('/connectivity-check', $connectivityComponent);
        $this->assertStringContainsString('Continue in Offline POS', $connectivityComponent);
        $this->assertGreaterThanOrEqual(50, substr_count($serviceWorker, '{url:"'));
    }
}
