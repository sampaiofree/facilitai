<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DescriptoService
{
    public function decryptToBase64(array $message, string $tipoMensagem): ?array
    {
        $content = $message['content'] ?? null;
        if (!is_array($content)) {
            return null;
        }

        $url = $content['URL'] ?? null;
        if (!$url && !empty($content['directPath'])) {
            $url = 'https://mmg.whatsapp.net' . $content['directPath'];
        }

        $mediaKeyB64 = $content['mediaKey'] ?? null;
        if (!$url || !$mediaKeyB64) {
            return null;
        }

        $response = Http::timeout(30)->retry(2, 500)->get($url);
        if ($response->failed()) {
            Log::error('DescriptoService download failed', [
                'status' => $response->status(),
                'url' => $url,
            ]);
            return null;
        }

        $encrypted = $response->body();
        $expectedEncHash = $content['fileEncSHA256'] ?? null;
        if ($expectedEncHash) {
            $actualEncHash = base64_encode(hash('sha256', $encrypted, true));
            if (!hash_equals($expectedEncHash, $actualEncHash)) {
                Log::error('DescriptoService encrypted hash mismatch', [
                    'expected' => $expectedEncHash,
                    'actual' => $actualEncHash,
                ]);
                return null;
            }
        }

        $mediaKey = base64_decode($mediaKeyB64, true);
        if ($mediaKey === false) {
            Log::error('DescriptoService mediaKey decode failed');
            return null;
        }

        $mediaType = $this->resolveMediaType($tipoMensagem, $content['mimetype'] ?? null);
        $info = "WhatsApp {$mediaType} Keys";
        $expanded = hash_hkdf('sha256', $mediaKey, 112, $info, '');
        $iv = substr($expanded, 0, 16);
        $cipherKey = substr($expanded, 16, 32);
        $macKey = substr($expanded, 48, 32);

        if (strlen($encrypted) < 10) {
            Log::error('DescriptoService encrypted payload too short');
            return null;
        }

        $mac = substr($encrypted, -10);
        $ciphertext = substr($encrypted, 0, -10);
        $calcMac = substr(hash_hmac('sha256', $iv . $ciphertext, $macKey, true), 0, 10);
        if (!hash_equals($mac, $calcMac)) {
            Log::error('DescriptoService MAC mismatch');
            return null;
        }

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-cbc', $cipherKey, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            Log::error('DescriptoService decrypt failed');
            return null;
        }

        $expectedHash = $content['fileSHA256'] ?? null;
        if ($expectedHash) {
            $actualHash = base64_encode(hash('sha256', $plaintext, true));
            if (!hash_equals($expectedHash, $actualHash)) {
                Log::error('DescriptoService file hash mismatch', [
                    'expected' => $expectedHash,
                    'actual' => $actualHash,
                ]);
                return null;
            }
        }

        return [
            'base64' => base64_encode($plaintext),
            'mimetype' => $content['mimetype'] ?? $this->defaultMimetype($mediaType),
        ];
    }

    private function resolveMediaType(string $tipoMensagem, ?string $mimetype): string
    {
        $tipo = Str::lower($tipoMensagem);
        if (str_contains($tipo, 'audio')) {
            return 'Audio';
        }
        if (str_contains($tipo, 'image')) {
            return 'Image';
        }
        if (str_contains($tipo, 'video')) {
            return 'Video';
        }
        if (str_contains($tipo, 'document')) {
            return 'Document';
        }

        if ($mimetype) {
            if (str_starts_with($mimetype, 'audio/')) {
                return 'Audio';
            }
            if (str_starts_with($mimetype, 'image/')) {
                return 'Image';
            }
            if (str_starts_with($mimetype, 'video/')) {
                return 'Video';
            }
        }

        return 'Document';
    }

    private function defaultMimetype(string $mediaType): string
    {
        return match ($mediaType) {
            'Audio' => 'audio/ogg',
            'Image' => 'image/jpeg',
            'Video' => 'video/mp4',
            default => 'application/octet-stream',
        };
    }
}
