<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UazapiJob;
use App\Support\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UazapiWebhookController extends Controller
{
    public function handle(Request $request, string $evento, string $tipoMensagem)
    {
        /*
        string $evento - Vem da URL geralmente por padrao messages
        string $tipoMensagem Vem da URL geralmente vem como: ImageMessage, DocumentMessage, AudioMessage e text
        */

        if ($evento !== 'messages') {
            Log::channel('uazapi_webhook')->info('Evento ignorado no webhook Uazapi.', LogContext::merge([
                'evento' => $evento,
                'tipo' => $tipoMensagem,
                'ip' => $request->ip(),
            ]));
            return response()->json(['status' => 'ignored']);
        }

        $payload = $request->json()->all();

        UazapiJob::dispatch([
            'evento' => $evento,
            'tipo' => $tipoMensagem,
            'payload' => $payload,
            'received_at' => now(),
        ])->onQueue('webhook');

        Log::channel('uazapi_webhook')->info('Webhook Uazapi enfileirado.', LogContext::merge([
            'evento' => $evento,
            'tipo' => $tipoMensagem,
            'ip' => $request->ip(),
        ]));

        return response()->json(['status' => 'queued']);
    }
}
