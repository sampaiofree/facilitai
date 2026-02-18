<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EvolutionAPIOficial
{
    private const TEXT_SPLIT_LIMIT = 800;

    public function instance_create(array $data): array
    {
        $validator = Validator::make($data, [
            'instanceName' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:2048'],
            'businessId' => ['required', 'string', 'max:255'],
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

        $integration = strtoupper(trim((string) config('services.evolution.oficial_integration', 'WHATSAPP-BUSINESS')));
        if ($integration === '') {
            $integration = 'WHATSAPP-BUSINESS';
        }

        $payload = [
            'instanceName' => $data['instanceName'],
            'integration' => $integration,
            'token' => $data['token'],
            'businessId' => $data['businessId'],
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

        $result = $this->performPost('/instance/create', $payload, 'instance_create', [
            'instanceName' => $data['instanceName'] ?? null,
        ]);
        if (!empty($result['error'])) {
            return $result;
        }

        $body = is_array($result['body'] ?? null) ? $result['body'] : [];

        return [
            'error' => false,
            'status' => $result['status'] ?? 200,
            'body' => $body,
            'hash' => $body['hash']
                ?? Arr::get($body, 'instance.hash')
                ?? Arr::get($body, 'instance.token'),
        ];
    }

    public function sendText(string $instanceId, string $number, string $text): array
    {
        $validator = Validator::make([
            'instanceId' => $instanceId,
            'number' => $number,
            'text' => $text,
        ], [
            'instanceId' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'regex:/^\d+$/', 'max:30'],
            'text' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'status' => 422,
                'body' => $validator->errors()->toArray(),
            ];
        }

        $textOriginal = trim((string) $text);
        if ($textOriginal === '') {
            return [
                'error' => true,
                'status' => 422,
                'body' => ['text' => ['O texto nao pode ser vazio.']],
            ];
        }

        if (mb_strlen($textOriginal) > self::TEXT_SPLIT_LIMIT) {
            $partes = $this->splitParagrafos($textOriginal, self::TEXT_SPLIT_LIMIT);
            $lastResponse = null;

            foreach ($partes as $index => $parte) {
                $lastResponse = $this->sendTextSingle($instanceId, $number, $parte);
                if (!empty($lastResponse['error'])) {
                    return $lastResponse;
                }

                if ($index < count($partes) - 1) {
                    usleep(300000);
                }
            }

            return $lastResponse ?? [
                'error' => true,
                'status' => 0,
                'body' => ['message' => 'Nenhuma parte enviada.'],
            ];
        }

        return $this->sendTextSingle($instanceId, $number, $textOriginal);
    }

    public function sendMedia(string $instanceId, string $number, string $type, string $file, array $options = []): array
    {
        $validator = Validator::make([
            'instanceId' => $instanceId,
            'number' => $number,
            'type' => $type,
            'file' => $file,
        ], [
            'instanceId' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'regex:/^\d+$/', 'max:30'],
            'type' => ['required', 'string', 'max:30'],
            'file' => ['required', 'string', 'max:4096'],
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'status' => 422,
                'body' => $validator->errors()->toArray(),
            ];
        }

        $normalizedType = $this->normalizeMediaType($type);
        if (!$normalizedType) {
            return [
                'error' => true,
                'status' => 422,
                'body' => ['type' => ['Tipo de mídia não suportado.']],
            ];
        }

        $caption = isset($options['text']) && is_string($options['text']) ? trim($options['text']) : '';
        $defaultFileName = in_array($normalizedType, ['ptt', 'audio'], true)
            ? 'audio.mp3'
            : $this->defaultFilenameForType($normalizedType);
        $fileName = isset($options['docName']) && is_string($options['docName']) && trim($options['docName']) !== ''
            ? trim($options['docName'])
            : ($this->extractFilename($file) ?? $defaultFileName);

        if ($normalizedType === 'ptt') {
            $audioExtension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
            $payload = [
                'number' => $number,
                'mediatype' => 'document',
                'media' => $file,
                'fileName' => $fileName,
                'caption' => $caption,
            ];

            if ($audioExtension === 'mp3') {
                $payload['audio'] = $file;

                return $this->performPost("/message/sendWhatsAppAudio/{$instanceId}", $payload, 'send_media_audio_legacy', [
                    'instanceId' => $instanceId,
                    'number' => $number,
                    'type' => 'audio',
                    'extension' => $audioExtension,
                ]);
            }

            return $this->performPost("/message/sendMedia/{$instanceId}", $payload, 'send_media_audio_legacy_fallback', [
                'instanceId' => $instanceId,
                'number' => $number,
                'type' => 'document',
                'extension' => $audioExtension,
            ]);
        }

        if ($normalizedType === 'audio') {
            $payload = [
                'number' => $number,
                'mediatype' => 'audio',
                'media' => $file,
                'fileName' => $fileName,
                'caption' => $caption,
            ];

            return $this->performPost("/message/sendMedia/{$instanceId}", $payload, 'send_media_audio', [
                'instanceId' => $instanceId,
                'number' => $number,
                'type' => 'audio',
            ]);
        }

        $payload = [
            'number' => $number,
            'mediatype' => $normalizedType,
            'media' => $file,
            'fileName' => $fileName,
            'caption' => $caption,
        ];

        return $this->performPost("/message/sendMedia/{$instanceId}", $payload, 'send_media', [
            'instanceId' => $instanceId,
            'number' => $number,
            'type' => $normalizedType,
        ]);
    }

    public function messagePresence(string $instanceId, string $number, string $presence = 'composing', int $delay = 7000): array
    {
        $validator = Validator::make([
            'instanceId' => $instanceId,
            'number' => $number,
            'presence' => $presence,
            'delay' => $delay,
        ], [
            'instanceId' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'regex:/^\d+$/', 'max:30'],
            'presence' => ['nullable', 'string', 'max:40'],
            'delay' => ['required', 'integer', 'min:100', 'max:120000'],
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'status' => 422,
                'body' => $validator->errors()->toArray(),
            ];
        }

        $presence = in_array($presence, ['composing', 'recording'], true) ? $presence : 'composing';
        $payload = [
            'number' => $number,
            'presence' => $presence,
            'delay' => $delay,
            'options' => [
                'presence' => $presence,
                'delay' => $delay,
                'number' => $number,
            ],
        ];

        return $this->performPost("/chat/sendPresence/{$instanceId}", $payload, 'message_presence', [
            'instanceId' => $instanceId,
            'number' => $number,
        ]);
    }

    private function sendTextSingle(string $instanceId, string $number, string $text): array
    {
        $payload = [
            'number' => $number,
            'text' => $text,
        ];

        return $this->performPost("/message/sendText/{$instanceId}", $payload, 'send_text', [
            'instanceId' => $instanceId,
            'number' => $number,
        ]);
    }

    private function performPost(string $endpoint, array $payload, string $context, array $logContext = []): array
    {
        $httpConfig = $this->resolveHttpConfig($context, $logContext);
        if (!empty($httpConfig['error'])) {
            return $httpConfig;
        }

        $url = $httpConfig['base_url'] . $endpoint;

        try {
            $response = Http::withHeaders($httpConfig['headers'])->post($url, $payload);
        } catch (\Throwable $exception) {
            Log::error("EvolutionAPIOficial::{$context} request exception", array_merge($logContext, [
                'error' => $exception->getMessage(),
            ]));

            return [
                'error' => true,
                'status' => null,
                'body' => ['message' => 'Falha de comunicação com a Evolution API Oficial.'],
            ];
        }

        return $this->normalizeResponse($response->status(), $response->json(), $response->body(), $context, $logContext);
    }

    private function resolveHttpConfig(string $context, array $logContext = []): array
    {
        $baseUrl = trim((string) config('services.evolution.url', ''));
        $apiKey = trim((string) config('services.evolution.key', ''));

        $missing = [];
        if ($baseUrl === '') {
            $missing[] = 'EVOLUTION_URL';
        }
        if ($apiKey === '') {
            $missing[] = 'EVOLUTION_GLOBAL_API_KEY';
        }

        if (!empty($missing)) {
            Log::error("EvolutionAPIOficial::{$context} configuração ausente", array_merge($logContext, [
                'missing_env' => $missing,
            ]));

            return [
                'error' => true,
                'status' => 500,
                'body' => [
                    'message' => 'Configuração da Evolution API Oficial incompleta.',
                    'missing_env' => $missing,
                ],
            ];
        }

        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            Log::error("EvolutionAPIOficial::{$context} EVOLUTION_URL inválida", array_merge($logContext, [
                'configured_url' => $baseUrl,
            ]));

            return [
                'error' => true,
                'status' => 500,
                'body' => [
                    'message' => 'Configuração inválida da Evolution API Oficial: EVOLUTION_URL.',
                ],
            ];
        }

        return [
            'error' => false,
            'base_url' => rtrim($baseUrl, '/'),
            'headers' => [
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ];
    }

    private function normalizeResponse(int $status, mixed $jsonBody, string $rawBody, string $context, array $logContext = []): array
    {
        $body = is_array($jsonBody) ? $jsonBody : ['raw' => $rawBody];

        if ($status >= 400) {
            Log::warning("EvolutionAPIOficial::{$context} failed", array_merge($logContext, [
                'status' => $status,
                'response' => $body,
            ]));

            return [
                'error' => true,
                'status' => $status,
                'body' => $body,
            ];
        }

        return [
            'error' => false,
            'status' => $status,
            'body' => $body,
        ];
    }

    private function splitParagrafos(string $mensagem, int $limite): array
    {
        $mensagem = str_replace(["\\r\\n", "\\r", "\\n"], "\n", $mensagem);
        $mensagem = trim(str_replace(["\r\n", "\r"], "\n", $mensagem));

        if ($mensagem === '') {
            return [''];
        }

        $paragrafos = preg_split('/\n\s*\n/', $mensagem) ?: [$mensagem];
        $partes = [];

        foreach ($paragrafos as $paragrafo) {
            $paragrafo = trim($paragrafo);
            if ($paragrafo === '') {
                continue;
            }

            if (mb_strlen($paragrafo) <= $limite) {
                $partes[] = $paragrafo;
                continue;
            }

            $restante = $paragrafo;
            while (mb_strlen($restante) > $limite) {
                $partes[] = mb_substr($restante, 0, $limite);
                $restante = mb_substr($restante, $limite);
            }
            if ($restante !== '') {
                $partes[] = $restante;
            }
        }

        return $partes ?: [$mensagem];
    }

    private function normalizeMediaType(string $type): ?string
    {
        $type = strtolower(trim($type));
        if ($type === '') {
            return null;
        }

        if (in_array($type, ['ptt', 'voice'], true)) {
            return 'ptt';
        }

        if ($type === 'audio') {
            return 'audio';
        }

        if ($type === 'pdf') {
            return 'document';
        }

        if (in_array($type, ['image', 'video', 'document', 'audio'], true)) {
            return $type;
        }

        return null;
    }

    private function extractFilename(string $file): ?string
    {
        $path = parse_url($file, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $name = basename($path);
        return $name !== '' ? $name : null;
    }

    private function defaultFilenameForType(string $type): string
    {
        return match ($type) {
            'audio' => 'audio.mp3',
            'image' => 'imagem.jpg',
            'video' => 'video.mp4',
            default => 'documento.pdf',
        };
    }
}
