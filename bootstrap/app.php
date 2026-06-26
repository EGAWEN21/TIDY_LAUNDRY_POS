<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\Admin;
use App\Http\Middleware\Store;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\Admin::class,
            'store' => \App\Http\Middleware\Store::class,
            'single.session' => \App\Http\Middleware\SingleSession::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'whatsapp/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
