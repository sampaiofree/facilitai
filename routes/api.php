<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EvolutionApiOficialWebhookController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\EvolutionWebhookController;
use App\Http\Controllers\Api\HotmartWebhookController;
use App\Http\Controllers\Api\UazapiWebhookController;
use App\Http\Controllers\Api\WhatsappCloudWebhookController;



/*Route::post('/conversation-test', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'received' => $request->all()
    ]);
});*/

Route::post('/webhook/asaas', [PaymentWebhookController::class, 'asaas']);
Route::post('/evolution-api-oficial', [EvolutionApiOficialWebhookController::class, 'handle'])->name('api.evolution-api-oficial');

//Route::post('/evolution', [EvolutionWebhookController::class, 'handle']);

//Route::post('/conversation', [EvolutionWebhookController::class, 'conversation']);

//Route::post('/hotmart', [HotmartWebhookController::class, 'handleWebhook']);

//Route::post('/hotmart-test-simple', [HotmartWebhookController::class, 'testHotmart']);

Route::post('/uazapi/{evento}/{tipodemensagem}', [UazapiWebhookController::class, 'handle'])->name('api.uazapi.handle');
Route::get('/whatsapp-cloud/{webhookKey}', [WhatsappCloudWebhookController::class, 'verify'])->name('api.whatsapp-cloud.webhook.verify');
Route::post('/whatsapp-cloud/{webhookKey}', [WhatsappCloudWebhookController::class, 'handle'])->name('api.whatsapp-cloud.webhook');
