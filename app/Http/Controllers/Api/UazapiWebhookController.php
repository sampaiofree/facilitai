<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UazapiJob;
use Illuminate\Http\Request;

class UazapiWebhookController extends Controller
{
    public function handle(Request $request, string $evento, string $tipoMensagem)
    {
        /*
        string $evento - Vem da URL geralmente por padrao messages
        string $tipoMensagem Vem da URL geralmente vem como: ImageMessage, DocumentMessage, AudioMessage e text
        */

        if ($evento !== 'messages') {
            return response()->json(['status' => 'ignored']);
        }

        $payload = $request->json()->all();

        UazapiJob::dispatch([
            'evento' => $evento,
            'tipo' => $tipoMensagem,
            'payload' => $payload,
            'received_at' => now(),
        ]);

        return response()->json(['status' => 'queued']);
    }
}
