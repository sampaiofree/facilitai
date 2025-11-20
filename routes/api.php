<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\EvolutionWebhookController;
use App\Http\Controllers\Api\HotmartWebhookController; 



Route::post('/conversation-test', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'received' => $request->all()
    ]);
});

Route::post('/webhook/asaas', [PaymentWebhookController::class, 'asaas']);

Route::post('/evolution', [EvolutionWebhookController::class, 'handle']);

Route::post('/conversation', [EvolutionWebhookController::class, 'conversation']);

Route::post('/hotmart', [HotmartWebhookController::class, 'handleWebhook']);

Route::post('/hotmart-test-simple', [HotmartWebhookController::class, 'testHotmart']);
