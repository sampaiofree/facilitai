<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MediaDecryptService
{
    protected DescriptoService $descripto;

    public function __construct(?DescriptoService $descripto = null)
    {
        $this->descripto = $descripto ?? new DescriptoService();
    }

    public function decrypt(array $message, string $tipoMensagem): ?array
    {
        // Valida se o tipo de mídia é suportado (audio, imagem, vídeo ou documento PDF).
        $tipoLower = Str::lower($tipoMensagem);
        $isDocument = str_contains($tipoLower, 'document');
        if ($isDocument && !$this->isPdf($message)) {
            Log::channel('media_decrypt')->warning('Documento não suportado para descriptografia.', [
                'tipo' => $tipoMensagem,
                'mimetype' => data_get($message, 'content.mimetype'),
                'filename' => data_get($message, 'content.fileName'),
            ]);
            return null;
        }

        $payload = $this->descripto->decryptToBase64($message, $tipoMensagem);
        if (!$payload || empty($payload['base64'])) {
            Log::channel('media_decrypt')->error('Falha ao descriptografar mídia.', [
                'tipo' => $tipoMensagem,
                'mimetype' => data_get($message, 'content.mimetype'),
                'filename' => data_get($message, 'content.fileName'),
            ]);
            return null;
        }

        return [
            'type' => $this->normalizeMediaType($tipoLower),
            'base64' => $payload['base64'],
            'mimetype' => $payload['mimetype'] ?? data_get($message, 'content.mimetype'),
        ];
    }

    protected function isPdf(array $message): bool
    {
        $mimetype = Str::lower((string) data_get($message, 'content.mimetype'));
        if ($mimetype === 'application/pdf') {
            return true;
        }

        $filename = Str::lower((string) data_get($message, 'content.fileName'));
        return $filename !== '' && str_ends_with($filename, '.pdf');
    }

    protected function normalizeMediaType(string $tipoLower): string
    {
        if (str_contains($tipoLower, 'audio')) {
            return 'audio';
        }
        if (str_contains($tipoLower, 'image')) {
            return 'image';
        }
        if (str_contains($tipoLower, 'video')) {
            return 'video';
        }

        return 'document';
    }
}
