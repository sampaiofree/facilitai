<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EvolutionAPIOficial
{
    public function instance_create(array $data): array
    {
        $validator = Validator::make($data, [
            'instanceName' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:2048'],
            'number' => ['required', 'string', 'regex:/^\d+$/', 'max:30'],
            'proxyHost' => ['required', 'string'],
            'proxyPort' => ['required', 'string'],
            'proxyUsername' => ['nullable', 'string'],
            'proxyPassword' => ['nullable', 'string'],
            'webhookUrl' => ['required', 'url'],
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'status' => 422,
                'body' => $validator->errors()->toArray(),
            ];
        }

        $payload = [
            'instanceName' => $data['instanceName'],
            'integration' => 'WHATSAPP_CLOUD_API',
            'token' => $data['token'],
            'number' => $data['number'],
            'proxyHost' => $data['proxyHost'],
            'proxyPort' => $data['proxyPort'],
            'proxyProtocol' => 'http',
            'proxyUsername' => $data['proxyUsername'] ?? null,
            'proxyPassword' => $data['proxyPassword'] ?? null,
            'webhook' => [
                'base64' => true,
                'events' => ['MESSAGES_UPSERT'],
                'url' => $data['webhookUrl'],
            ],
        ];

        $url = rtrim((string) config('services.evolution.url'), '/') . '/instance/create';

        try {
            $response = Http::withHeaders([
                'apiKey' => (string) config('services.evolution.key'),
            ])->post($url, $payload);
        } catch (\Throwable $exception) {
            Log::error('EvolutionAPIOficial::instance_create request exception', [
                'instanceName' => $data['instanceName'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return [
                'error' => true,
                'status' => null,
                'body' => ['message' => 'Falha de comunicação com a Evolution API Oficial.'],
            ];
        }

        $body = $response->json();
        if (!is_array($body)) {
            $body = ['raw' => $response->body()];
        }

        if ($response->failed()) {
            Log::warning('EvolutionAPIOficial::instance_create failed', [
                'instanceName' => $data['instanceName'] ?? null,
                'status' => $response->status(),
                'response' => $body,
            ]);

            return [
                'error' => true,
                'status' => $response->status(),
                'body' => $body,
            ];
        }

        return [
            'error' => false,
            'status' => $response->status(),
            'body' => $body,
            'hash' => $body['hash']
                ?? Arr::get($body, 'instance.hash')
                ?? Arr::get($body, 'instance.token'),
        ];
    }
}
