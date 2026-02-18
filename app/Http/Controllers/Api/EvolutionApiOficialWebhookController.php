<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\EvolutionApiOficialJob;
use App\Support\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EvolutionApiOficialWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->json()->all();
        if (!is_array($payload)) {
            $payload = [];
        }

        $event = $this->normalizeEvent((string) (Arr::get($payload, 'event') ?? Arr::get($payload, 'type') ?? ''));
        $instance = Arr::get($payload, 'instance') ?? Arr::get($payload, 'instanceName');

        if ($event !== 'messages.upsert') {
            Log::channel('evolution_oficial_webhook')->info('Evento ignorado no webhook Evolution API Oficial.', LogContext::merge([
                'event' => $event,
                'instance' => $instance,
                'ip' => $request->ip(),
                'provider' => 'api_oficial',
            ]));

            return response()->json(['status' => 'ignored']);
        }

        // Remove campos sensiveis antes de persistir no payload da fila.
        unset($payload['apikey']);

        EvolutionApiOficialJob::dispatch([
            'event' => $event,
            'payload' => $payload,
            'received_at' => now(),
        ])->onQueue('webhook');

        Log::channel('evolution_oficial_webhook')->info('Webhook Evolution API Oficial enfileirado.', LogContext::merge([
            'event' => $event,
            'instance' => $instance,
            'ip' => $request->ip(),
            'provider' => 'api_oficial',
        ]));

        return response()->json(['status' => 'queued']);
    }

    private function normalizeEvent(string $event): string
    {
        if ($event === '') {
            return '';
        }

        return (string) Str::of(trim($event))
            ->lower()
            ->replace('-', '.');
    }
}
