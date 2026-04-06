<?php

namespace App\Services;

use App\Models\ClienteLead;
use App\Models\KommoAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class KommoService
{
    private const DEFAULT_TIMEOUT_SECONDS = 15;

    private const PIPELINES_PATH = '/api/v4/leads/pipelines';
    private const COMPLEX_LEADS_PATH = '/api/v4/leads/complex';

    public function normalizeSubdomain(string $value): string
    {
        $value = trim(mb_strtolower($value));

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $host = parse_url($value, PHP_URL_HOST);
            $value = is_string($host) ? $host : $value;
        }

        $value = preg_replace('/[\/?#].*$/', '', $value) ?? $value;
        $value = preg_replace('/:\d+$/', '', $value) ?? $value;

        if (str_ends_with($value, '.kommo.com')) {
            $value = substr($value, 0, -strlen('.kommo.com'));
        }

        return trim($value, '. ');
    }

    public function validateAccount(string $subdomain, string $token): array
    {
        $prepared = $this->prepareCredentials($subdomain, $token);
        if (!$prepared['ok']) {
            return [
                'ok' => false,
                'account_id' => null,
                'account_name' => null,
                'message' => $prepared['message'],
                'status' => $prepared['status'],
                'raw' => null,
            ];
        }

        $url = $this->accountUrl($prepared['subdomain']);

        try {
            $response = $this->makeRequest($url, $prepared['token']);
        } catch (Throwable $exception) {
            Log::warning('Falha ao validar conta Kommo.', [
                'subdomain' => $prepared['subdomain'],
                'exception' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'account_id' => null,
                'account_name' => null,
                'message' => 'Não foi possível conectar ao Kommo agora. Tente novamente.',
                'status' => 0,
                'raw' => null,
            ];
        }

        $raw = $response->json();

        if (!$response->successful()) {
            return [
                'ok' => false,
                'account_id' => null,
                'account_name' => null,
                'message' => $this->resolveFailureMessage($response->status(), $raw),
                'status' => $response->status(),
                'raw' => $raw,
            ];
        }

        return [
            'ok' => true,
            'account_id' => data_get($raw, 'id') !== null ? (string) data_get($raw, 'id') : null,
            'account_name' => trim((string) (data_get($raw, 'name') ?? '')),
            'message' => 'Conexão com o Kommo validada com sucesso.',
            'status' => $response->status(),
            'raw' => $raw,
            'subdomain' => $prepared['subdomain'],
        ];
    }

    public function listPipelines(string $subdomain, string $token): array
    {
        $prepared = $this->prepareCredentials($subdomain, $token);
        if (!$prepared['ok']) {
            return [
                'ok' => false,
                'message' => $prepared['message'],
                'status' => $prepared['status'],
                'pipelines' => [],
                'raw' => null,
            ];
        }

        $url = $this->apiUrl($prepared['subdomain'], self::PIPELINES_PATH);

        try {
            $response = $this->makeRequest($url, $prepared['token']);
        } catch (Throwable $exception) {
            Log::warning('Falha ao listar pipelines do Kommo.', [
                'subdomain' => $prepared['subdomain'],
                'exception' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Não foi possível carregar as pipelines do Kommo agora. Tente novamente.',
                'status' => 0,
                'pipelines' => [],
                'raw' => null,
            ];
        }

        $raw = $response->json();

        if (!$response->successful()) {
            return [
                'ok' => false,
                'message' => $this->resolveFailureMessage($response->status(), $raw, 'Não foi possível carregar as pipelines do Kommo.'),
                'status' => $response->status(),
                'pipelines' => [],
                'raw' => $raw,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Pipelines do Kommo carregadas com sucesso.',
            'status' => $response->status(),
            'pipelines' => $this->normalizeCollection(data_get($raw, '_embedded.pipelines', $raw)),
            'raw' => $raw,
            'subdomain' => $prepared['subdomain'],
        ];
    }

    public function listStatuses(string $subdomain, string $token, string $pipelineId): array
    {
        $prepared = $this->prepareCredentials($subdomain, $token);
        if (!$prepared['ok']) {
            return [
                'ok' => false,
                'message' => $prepared['message'],
                'status' => $prepared['status'],
                'statuses' => [],
                'raw' => null,
            ];
        }

        $normalizedPipelineId = trim($pipelineId);
        if ($normalizedPipelineId === '') {
            return [
                'ok' => false,
                'message' => 'Selecione uma pipeline Kommo válida.',
                'status' => 422,
                'statuses' => [],
                'raw' => null,
            ];
        }

        $url = $this->apiUrl($prepared['subdomain'], sprintf('%s/%s/statuses', self::PIPELINES_PATH, rawurlencode($normalizedPipelineId)));

        try {
            $response = $this->makeRequest($url, $prepared['token']);
        } catch (Throwable $exception) {
            Log::warning('Falha ao listar estágios do Kommo.', [
                'subdomain' => $prepared['subdomain'],
                'pipeline_id' => $normalizedPipelineId,
                'exception' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Não foi possível carregar os estágios do Kommo agora. Tente novamente.',
                'status' => 0,
                'statuses' => [],
                'raw' => null,
            ];
        }

        $raw = $response->json();

        if (!$response->successful()) {
            return [
                'ok' => false,
                'message' => $this->resolveFailureMessage($response->status(), $raw, 'Não foi possível carregar os estágios do Kommo.'),
                'status' => $response->status(),
                'statuses' => [],
                'raw' => $raw,
            ];
        }

        return [
            'ok' => true,
            'message' => 'Estágios do Kommo carregados com sucesso.',
            'status' => $response->status(),
            'statuses' => $this->normalizeCollection(data_get($raw, '_embedded.statuses', $raw)),
            'raw' => $raw,
            'subdomain' => $prepared['subdomain'],
            'pipeline_id' => $normalizedPipelineId,
        ];
    }

    public function accountUrl(string $subdomain): string
    {
        return $this->apiUrl($subdomain, '/api/v4/account');
    }

    public function sendLeadToAccount(KommoAccount $account, ClienteLead $lead): array
    {
        $subdomain = $this->normalizeSubdomain((string) $account->subdomain);
        $token = (string) $account->access_token;
        $prepared = $this->prepareCredentials($subdomain, $token);

        if (!$prepared['ok']) {
            return [
                'ok' => false,
                'message' => $prepared['message'],
                'lead_id' => null,
                'contact_id' => null,
                'status' => $prepared['status'],
                'raw' => null,
            ];
        }

        $phone = trim((string) ($lead->phone ?? ''));
        if ($phone === '') {
            return [
                'ok' => false,
                'message' => 'O lead atual não possui telefone para envio ao Kommo.',
                'lead_id' => null,
                'contact_id' => null,
                'status' => 422,
                'raw' => null,
            ];
        }

        $payload = $this->buildComplexLeadPayload($account, $lead);
        $url = $this->apiUrl($prepared['subdomain'], self::COMPLEX_LEADS_PATH);

        try {
            $response = $this->makeJsonRequest('POST', $url, $prepared['token'], $payload);
        } catch (Throwable $exception) {
            Log::warning('Falha ao enviar lead para o Kommo.', [
                'kommo_account_id' => $account->id,
                'lead_id' => $lead->id,
                'subdomain' => $prepared['subdomain'],
                'exception' => $exception->getMessage(),
            ]);

            return [
                'ok' => false,
                'message' => 'Não foi possível conectar ao Kommo para enviar o lead agora. Tente novamente.',
                'lead_id' => null,
                'contact_id' => null,
                'status' => 0,
                'raw' => null,
            ];
        }

        $raw = $response->json();

        if (!$response->successful()) {
            return [
                'ok' => false,
                'message' => $this->resolveFailureMessage($response->status(), $raw, 'O Kommo rejeitou o envio do lead.'),
                'lead_id' => null,
                'contact_id' => null,
                'status' => $response->status(),
                'raw' => $raw,
            ];
        }

        $entry = $this->firstEmbeddedEntry(data_get($raw, '_embedded.leads', $raw));

        return [
            'ok' => true,
            'message' => 'Lead enviado ao Kommo com sucesso.',
            'lead_id' => data_get($entry, 'id') !== null ? (string) data_get($entry, 'id') : null,
            'contact_id' => data_get($entry, '_embedded.contacts.0.id') !== null
                ? (string) data_get($entry, '_embedded.contacts.0.id')
                : null,
            'status' => $response->status(),
            'raw' => $raw,
        ];
    }

    public function buildComplexLeadPayload(KommoAccount $account, ClienteLead $lead): array
    {
        $contactName = trim((string) ($lead->name ?? ''));
        $phone = trim((string) ($lead->phone ?? ''));

        if ($contactName === '') {
            $contactName = $phone !== '' ? $phone : 'Contato sem nome';
        }

        return [[
            'name' => 'Lead enviado pelo assistente',
            'pipeline_id' => (int) $account->pipeline_id,
            'status_id' => (int) $account->status_id,
            '_embedded' => [
                'contacts' => [[
                    'name' => $contactName,
                    'custom_fields_values' => [[
                        'field_code' => 'PHONE',
                        'values' => [[
                            'value' => $phone,
                        ]],
                    ]],
                ]],
            ],
        ]];
    }

    private function apiUrl(string $subdomain, string $path): string
    {
        return sprintf('https://%s.kommo.com%s', $this->normalizeSubdomain($subdomain), $path);
    }

    private function prepareCredentials(string $subdomain, string $token): array
    {
        $normalizedSubdomain = $this->normalizeSubdomain($subdomain);
        $normalizedToken = trim($token);

        if ($normalizedSubdomain === '') {
            return [
                'ok' => false,
                'message' => 'Informe um subdomínio Kommo válido.',
                'status' => 422,
            ];
        }

        if (!preg_match('/^[a-z0-9-]+$/', $normalizedSubdomain)) {
            return [
                'ok' => false,
                'message' => 'O subdomínio Kommo deve conter apenas letras, números e hífen.',
                'status' => 422,
            ];
        }

        if ($normalizedToken === '') {
            return [
                'ok' => false,
                'message' => 'Informe o long-lived token do Kommo.',
                'status' => 422,
            ];
        }

        return [
            'ok' => true,
            'subdomain' => $normalizedSubdomain,
            'token' => $normalizedToken,
            'status' => 200,
            'message' => null,
        ];
    }

    private function makeRequest(string $url, string $token)
    {
        return Http::acceptJson()
            ->withToken($token)
            ->timeout(self::DEFAULT_TIMEOUT_SECONDS)
            ->get($url);
    }

    private function makeJsonRequest(string $method, string $url, string $token, array $payload)
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->timeout(self::DEFAULT_TIMEOUT_SECONDS)
            ->send(Str::upper($method), $url, [
                'json' => $payload,
            ]);
    }

    private function normalizeCollection(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (!is_array($item) || data_get($item, 'id') === null) {
                continue;
            }

            $normalized[] = [
                'id' => (string) data_get($item, 'id'),
                'name' => trim((string) (data_get($item, 'name') ?? '')),
            ];
        }

        return $normalized;
    }

    private function firstEmbeddedEntry(mixed $items): array
    {
        if (is_array($items) && array_is_list($items)) {
            return isset($items[0]) && is_array($items[0]) ? $items[0] : [];
        }

        return is_array($items) ? $items : [];
    }

    private function resolveFailureMessage(int $status, mixed $raw, string $fallback = 'A validação da conta Kommo falhou.'): string
    {
        $apiMessage = data_get($raw, 'detail')
            ?? data_get($raw, 'title')
            ?? data_get($raw, 'message')
            ?? data_get($raw, 'hint');

        if (is_string($apiMessage) && trim($apiMessage) !== '') {
            return trim($apiMessage);
        }

        return match ($status) {
            401, 403 => 'O token do Kommo é inválido ou não possui acesso à conta.',
            404 => 'Não foi possível localizar a conta Kommo para este subdomínio.',
            default => $fallback,
        };
    }
}
