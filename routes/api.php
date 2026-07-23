<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PosApiController;

Route::post('/login', [PosApiController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', \App\Http\Middleware\AuthorizePosAccess::class])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/pos/init', [PosApiController::class, 'init']);
    Route::get('/pos/check-update', [PosApiController::class, 'checkUpdates']);
    Route::post('/pos/sync-customers', [PosApiController::class, 'syncCustomers'])->middleware('throttle:10,1');
    Route::post('/pos/sync-orders', [PosApiController::class, 'syncOrders'])->middleware('throttle:10,1');
    Route::get('/pos/rejected-orders', [PosApiController::class, 'getRejectedOrders']);
});
