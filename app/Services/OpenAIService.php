<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class OpenAIService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $apiKey = trim($apiKey);
        if ($apiKey === '' || $apiKey === '******') {
            Log::channel('openai')->error('OpenAIService missing api key');
            throw new \RuntimeException('Token da OpenAI não configurado na credencial vinculada.');
        }

        if ($baseUrl) {
            $this->baseUrl = $baseUrl;
        }

        $this->apiKey = $apiKey;
    }

    public function createConversation(array $payload, array $options = []): ?Response
    {
        return $this->request('post', '/conversations', $payload, $options);
    }

    public function createItems(string $conversationId, array $payload, array $options = []): ?Response
    {
        return $this->request('post', "/conversations/{$conversationId}/items", $payload, $options);
    }

    public function createResponse(array $payload, array $options = []): ?Response
    {
        return $this->request('post', '/responses', $payload, $options);
    }

    public function getConversationItems(string $conversationId, array $query = [], array $options = []): ?Response
    {
        return $this->request('get', "/conversations/{$conversationId}/items", $query, $options);
    }

    public function transcreverAudio(string $base64, array $options = []): ?Response
    {
        $tmpPath = storage_path('app/tmp/');
        $suffix = uniqid('', true);
        $originalAudioPath = $tmpPath . 'openai_audio_' . $suffix . '.ogg';
        $convertedAudioPath = $tmpPath . 'openai_audio_' . $suffix . '.mp3';

        $model = (string) ($options['model'] ?? 'whisper-1');
        $language = (string) ($options['language'] ?? 'pt');

        try {
            // Garante o diretório temporário antes de criar arquivos.
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath, 0777, true);
            }

            // Decodifica o áudio recebido em base64 (falha aqui indica mídia inválida).
            $binary = base64_decode($base64, true);
            if ($binary === false) {
                Log::channel('openai')->error('OpenAIService transcreverAudio base64 inválido');
                return null;
            }

            file_put_contents($originalAudioPath, $binary);

            // Tenta converter com ffmpeg quando disponível; se falhar, mantém o .ogg original.
            $uploadPath = $originalAudioPath;
            if ($this->isFfmpegAvailable()) {
                $result = Process::run('ffmpeg -i "' . $originalAudioPath . '" -acodec libmp3lame -q:a 2 "' . $convertedAudioPath . '" -y');
                if ($result->successful()) {
                    $uploadPath = $convertedAudioPath;
                } else {
                    Log::channel('openai')->warning('OpenAIService transcreverAudio ffmpeg falhou, usando ogg original', [
                        'exit_code' => $result->exitCode(),
                        'error' => $result->errorOutput(),
                    ]);
                }
            } else {
                Log::channel('openai')->warning('OpenAIService transcreverAudio ffmpeg não encontrado, usando ogg original');
            }

            // Envia o arquivo de áudio para transcrição.
            $fileHandle = fopen($uploadPath, 'r');
            $payload = [
                'file' => $fileHandle,
                'model' => $model,
                'language' => $language,
            ];

            $response = $this->request('post', '/audio/transcriptions', $payload, array_merge($options, [
                'multipart' => true,
            ]));
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::channel('openai')->error('OpenAIService transcreverAudio exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        } finally {
            // Remove arquivos temporários criados para a transcrição.
            if (file_exists($originalAudioPath)) {
                unlink($originalAudioPath);
            }
            if (file_exists($convertedAudioPath)) {
                unlink($convertedAudioPath);
            }
        }
    }

    private function isFfmpegAvailable(): bool
    {
        try {
            $result = Process::run('ffmpeg -version');
            return $result->successful();
        } catch (\Throwable $e) {
            Log::channel('openai')->warning('OpenAIService ffmpeg check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function request(string $method, string $path, array $payload = [], array $options = []): ?Response
    {
        $timeout = (int) ($options['timeout'] ?? 120);
        $maxRetries = (int) ($options['max_retries'] ?? 2);
        $baseDelayMs = (int) ($options['base_delay_ms'] ?? 1000);
        $maxDelayMs = (int) ($options['max_delay_ms'] ?? 8000);
        $headers = is_array($options['headers'] ?? null) ? $options['headers'] : [];
        $multipart = (bool) ($options['multipart'] ?? false);
        $logContext = is_array($options['log_context'] ?? null) ? $options['log_context'] : [];

        $url = $this->baseUrl . $path;
        $attempt = 0;

        do {
            $attempt++;

            try {
                $client = Http::withToken($this->apiKey)
                    ->timeout($timeout);

                if (!empty($headers)) {
                    $client = $client->withHeaders($headers);
                }

                if ($multipart) {
                    $client = $client->asMultipart();
                }

                $response = $method === 'get'
                    ? $client->get($url, $payload)
                    : $client->post($url, $payload);

                if ($response->successful()) {
                    return $response;
                }

                if (!$this->shouldRetry($response) || $attempt > $maxRetries) {
                    return $response;
                }

                $this->sleepWithBackoff($attempt, $baseDelayMs, $maxDelayMs);
            } catch (\Throwable $e) {
                if ($attempt > $maxRetries) {
                    Log::channel('openai')->error('OpenAIService request exception', array_merge($logContext, [
                        'error' => $e->getMessage(),
                    ]));
                    return null;
                }

                $this->sleepWithBackoff($attempt, $baseDelayMs, $maxDelayMs);
            }
        } while ($attempt <= $maxRetries);

        return $response ?? null;
    }

    protected function shouldRetry(?Response $response): bool
    {
        if (!$response) {
            return true;
        }

        $status = $response->status();
        if (in_array($status, [408, 429], true) || $status >= 500) {
            return true;
        }

        $errorCode = $response->json('error.code');
        return in_array($errorCode, ['conversation_locked', 'rate_limit_exceeded'], true);
    }

    protected function sleepWithBackoff(int $attempt, int $baseDelayMs, int $maxDelayMs): void
    {
        $expDelay = $baseDelayMs * (2 ** max(0, $attempt - 1));
        $delayMs = min($expDelay, $maxDelayMs);
        $jitter = random_int(0, 250);
        usleep((int) (($delayMs + $jitter) * 1000));
    }
}
