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
        // Valida se o tipo de mídia é suportado (audio, imagem, vídeo ou documento em whitelist).
        $tipoLower = Str::lower($tipoMensagem);
        $isDocument = str_contains($tipoLower, 'document');
        if ($isDocument && !$this->isAllowedDocument($message)) {
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

    protected function isAllowedDocument(array $message): bool
    {
        $mimetype = Str::lower((string) data_get($message, 'content.mimetype'));
        $allowedMimetypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];

        if ($mimetype !== '' && in_array($mimetype, $allowedMimetypes, true)) {
            return true;
        }

        $filename = Str::lower((string) data_get($message, 'content.fileName'));
        if ($filename === '') {
            return false;
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'], true);
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
