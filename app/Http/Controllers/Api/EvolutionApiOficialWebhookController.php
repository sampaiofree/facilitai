<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class EvolutionApiOficialWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->json()->all();

        Log::info('Webhook Evolution API Oficial recebido.', [
            'ip' => $request->ip(),
            'event' => Arr::get($payload, 'event') ?? Arr::get($payload, 'type'),
            'instance' => Arr::get($payload, 'instance') ?? Arr::get($payload, 'instanceName'),
        ]);

        return response()->json(['status' => 'received']);
    }
}
