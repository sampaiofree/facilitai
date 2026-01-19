<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UazapiService
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.uazapi.url', ''), '/');
        $this->token = config('services.uazapi.token');
    }

    public function request(string $method, string $endpoint, array $payload = [], array $query = []): array
    {
        $method = strtoupper($method);
        $url = $this->buildUrl($endpoint);

        $options = [];
        if (!empty($query)) {
            $options['query'] = $query;
        }
        if (!empty($payload) && $method !== 'GET') {
            $options['json'] = $payload;
        }

        try {
            $client = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

            if ($this->token) {
                $client = $client->withToken($this->token);
            }

            $response = $client->send($method, $url, $options);

            if ($response->successful()) {
                $decoded = $response->json();
                return $decoded ?? ['body' => $response->body()];
            }

            Log::warning('UazapiService request failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->buildErrorPayload($response->status(), $response->json() ?? $response->body());
        } catch (RequestException $exception) {
            $status = $exception->response?->status() ?? 0;
            $body = $exception->response?->json() ?? $exception->response?->body() ?? $exception->getMessage();

            Log::error('UazapiService HTTP exception', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $status,
                'body' => $body,
                'exception' => $exception->getMessage(),
            ]);

            return $this->buildErrorPayload($status, $body);
        } catch (\Throwable $exception) {
            Log::error('UazapiService unexpected error', [
                'method' => $method,
                'endpoint' => $endpoint,
                'exception' => $exception->getMessage(),
            ]);

            return $this->buildErrorPayload(0, $exception->getMessage());
        }
    }

    protected function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');

        if ($this->baseUrl === '') {
            return $endpoint;
        }

        return $endpoint === '' ? $this->baseUrl : "{$this->baseUrl}/{$endpoint}";
    }

    protected function buildErrorPayload(int $status, mixed $body): array
    {
        return [
            'error' => true,
            'status' => $status,
            'body' => $body,
        ];
    }
}
