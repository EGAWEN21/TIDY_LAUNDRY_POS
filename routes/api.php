<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PosApiController;

Route::post('/login', [PosApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/pos/init', [PosApiController::class, 'init']);
    Route::get('/pos/check-update', [PosApiController::class, 'checkUpdates']);
    Route::post('/pos/sync-customers', [PosApiController::class, 'syncCustomers']);
    Route::post('/pos/sync-orders', [PosApiController::class, 'syncOrders']);
    Route::get('/pos/rejected-orders', [PosApiController::class, 'getRejectedOrders']);
});
