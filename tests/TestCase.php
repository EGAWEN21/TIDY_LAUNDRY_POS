<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->guardTestEnvironment($app);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Mail::fake();
        Notification::fake();
        Http::preventStrayRequests();
    }

    private function guardTestEnvironment(Application $app): void
    {
        $expectedDatabase = realpath($app->databasePath('testing.sqlite'));
        $configuredDatabase = realpath((string) $app['config']->get('database.connections.sqlite.database'));

        if (! $app->environment('testing')) {
            throw new RuntimeException('The automated test suite must run in the testing environment.');
        }

        if ($app['config']->get('database.default') !== 'sqlite'
            || $expectedDatabase === false
            || $configuredDatabase !== $expectedDatabase) {
            throw new RuntimeException('The automated test suite must use database/testing.sqlite.');
        }
    }
}
