<?php

namespace App\Services;

use App\Models\UazapiInstance;
use App\Services\WebshareService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ResponseInterface;


class UazapiService
{
    protected string $adminToken;
    protected string $baseUrl;
    protected Client $client;

    public function __construct()
    {
        // Pega o token e a URL base do .env para maior segurança e flexibilidade
        $this->adminToken = (string) config('services.uazapi.token');
        $this->baseUrl = (string) config('services.uazapi.url');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function instance_init(array $data): array 
    {
        $validator = Validator::make($data, [
            'name' => 'required|string',
            'systemName' => 'nullable|string',
            'adminField01' => 'nullable|string',
            'adminField02' => 'nullable|string',
            'fingerprintProfile' => 'nullable|string',
            'browser' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            Log::channel('uazapi')->error('UazapiService::instance_init validation failed', ['errors' => $errors]);

            return [
                'error' => true,
                'status' => 422,
                'body' => $errors,
            ];
        }

        try {
            $response = $this->client->post('/instance/init', [
                'headers' => $this->adminHeaders(),
                'json' => $validator->validated(),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_init', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_init', $exception);
        }
    }

    public function instance_all(): array
    {
        try {
            $response = $this->client->get('/instance/all', [
                'headers' => $this->adminHeaders(),
            ]);
            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_all', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_all', $exception);
        }
    }

    public function instance_connect(string $token, ?string $phone = null): array
    {
        $sanitizedPhone = $phone !== null ? preg_replace('/\s+/', '', $phone) : null;

        $validator = Validator::make(['phone' => $sanitizedPhone], [
            'phone' => ['nullable', 'string', 'regex:/^\d+$/', 'min:11', 'max:15'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            Log::channel('uazapi')->error('UazapiService::instance_connect validation failed', ['errors' => $errors]);

            return [
                'error' => true,
                'status' => 422,
                'body' => $errors,
            ];
        }

        $validated = array_filter($validator->validated(), function ($value) {
            return $value !== null && $value !== '';
        });

        $options = [
            'headers' => $this->instanceHeaders($token),
        ];

        if (!empty($validated)) {
            $options['json'] = $validated;
        }

        try {
            $response = $this->client->post('/instance/connect', $options);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_connect', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_connect', $exception);
        }
    }

    public function instance_status(string $token): array
    {
        try {
            $response = $this->client->get('/instance/status', [
                'headers' => $this->instanceHeaders($token),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_status', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_status', $exception);
        }
    }

    public function instance_disconnect(string $token): array
    {
        try {
            $response = $this->client->post('/instance/disconnect', [
                'headers' => $this->instanceHeaders($token),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_disconnect', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_disconnect', $exception);
        }
    }

    public function instance_delete(string $token): array
    {
        try {
            $response = $this->client->delete('/instance', [
                'headers' => $this->instanceHeaders($token),
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_delete', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_delete', $exception);
        }
    }

    public function chat_check(array $numbers, ?string $token = null): array
    {
        $normalized = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $numbers);
        $normalized = array_values(array_filter($normalized, fn ($value) => $value !== null && $value !== ''));

        $validator = Validator::make(['numbers' => $normalized], [
            'numbers' => ['required', 'array', 'min:1'],
            'numbers.*' => ['required', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            Log::channel('uazapi')->error('UazapiService::chat_check validation failed', ['errors' => $errors]);

            return [
                'error' => true,
                'status' => 422,
                'body' => $errors,
            ];
        }

        $endpoint = rtrim($this->baseUrl ?: 'https://free.uazapi.com', '/') . '/chat/check';

        try {
            $options = [
                'json' => $validator->validated(),
            ];
            if ($token !== null && $token !== '') {
                $options['headers'] = $this->instanceHeaders($token);
            }

            $response = $this->client->post($endpoint, $options);

            $payload = $this->successResponse($response);
            $payload['status'] = $response->getStatusCode();

            return $payload;
        } catch (RequestException $exception) {
            return $this->handleRequestException('chat_check', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('chat_check', $exception);
        }
    }

    public function sendText(string $token, string $number, string $text): array
    {
        try {
            $response = $this->client->post('/send/text', [
                'headers' => $this->instanceHeaders($token),
                'json' => [
                    'number' => $number,
                    'text' => $text,
                ],
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('send_text', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('send_text', $exception);
        }
    }

    public function sendMedia(string $token, string $number, string $type, string $file, array $options = []): array
    {
        $payload = array_merge([
            'number' => $number,
            'type' => $type,
            'file' => $file,
        ], $options);

        try {
            $response = $this->client->post('/send/media', [
                'headers' => $this->instanceHeaders($token),
                'json' => $payload,
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('send_media', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('send_media', $exception);
        }
    }

    public function messagePresence(string $token, string $number, string $presence = 'composing', int $delay = 30000): array
    {
        $payload = [
            'number' => $number,
            'presence' => $presence,
            'delay' => $delay,
        ];

        try {
            $response = $this->client->post('/message/presence', [
                'headers' => $this->instanceHeaders($token),
                'json' => $payload,
            ]);

            return $this->successResponse($response);
        } catch (RequestException $exception) {
            return $this->handleRequestException('message_presence', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('message_presence', $exception);
        }
    }

    public function instance_proxy(string $token): array
    {
        try {
            $response = $this->client->get('/instance/proxy', [
                'headers' => $this->instanceHeaders($token),
            ]);

            $payload = $this->successResponse($response);
            Log::channel('uazapi')->info('UazapiService instance_proxy response', [
                'token' => $token,
                'response' => $payload,
            ]);

            return $payload;
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_proxy', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_proxy', $exception);
        }
    }

    public function configure_proxy(string $token): array
    {
        $webshare = new WebshareService();
        $proxyData = $webshare->getNewProxy();
        $proxyUrl = sprintf(
            'http://%s:%s@%s:%s',
            $proxyData['username'],
            $proxyData['password'],
            $proxyData['proxy_address'],
            (string) $proxyData['port']
        );
        $payload = [
            'enable' => true,
            'proxy_url' => $proxyUrl,
        ];

        try {
            $response = $this->client->post('/instance/proxy', [
                'headers' => $this->instanceHeaders($token),
                'json' => $payload,
            ]);

            $decoded = $this->successResponse($response);
            Log::channel('uazapi')->info('UazapiService instance_proxy configure response', [
                'token' => $token,
                'payload' => $payload,
                'proxy_ip' => $proxyData['proxy_address'] ?? null,
                'response' => $decoded,
            ]);

            $instance = UazapiInstance::where('token', $token)->first();
            if ($instance) {
                $instance->update(['proxy_ip' => $proxyData['proxy_address']]);
            }

            return $decoded;
        } catch (RequestException $exception) {
            return $this->handleRequestException('instance_proxy_configure', $exception);
        } catch (\Throwable $exception) {
            return $this->handleUnexpectedError('instance_proxy_configure', $exception);
        }
    }

    protected function parseJson(?string $payload): mixed
    {
        if ($payload === null) {
            return null;
        }

        $decoded = json_decode($payload, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $payload;
    }

    protected function successResponse(ResponseInterface $response): array
    {
        $payload = (string) $response->getBody();
        $decoded = $this->parseJson($payload);

        return is_array($decoded) ? $decoded : ['body' => $decoded];
    }

    protected function handleRequestException(string $context, RequestException $exception): array
    {
        $status = $exception->hasResponse() ? $exception->getResponse()->getStatusCode() : 0;
        $body = $exception->hasResponse() ? (string) $exception->getResponse()->getBody() : null;

        Log::channel('uazapi')->error("UazapiService::{$context} request failed", [
            'status' => $status,
            'body' => $body,
            'exception' => $exception->getMessage(),
        ]);

        return [
            'error' => true,
            'status' => $status,
            'body' => $this->parseJson($body),
        ];
    }

    protected function handleUnexpectedError(string $context, \Throwable $exception): array
    {
        Log::channel('uazapi')->error("UazapiService::{$context} unexpected error", [
            'exception' => $exception->getMessage(),
        ]);

        return [
            'error' => true,
            'status' => 0,
            'body' => $exception->getMessage(),
        ];
    }

    protected function adminHeaders(): array
    {
        return [
            'admintoken' => $this->adminToken,
        ];
    }

    protected function instanceHeaders(string $token): array
    {
        return [
            'token' => $token,
        ];
    }
}
