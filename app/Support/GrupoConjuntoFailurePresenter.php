<?php

namespace App\Support;

use App\Models\GrupoConjuntoMensagem;
use Illuminate\Support\Str;

class GrupoConjuntoFailurePresenter
{
    public const UNKNOWN_FAILURE_MESSAGE = 'Não foi possível identificar o motivo da falha.';

    public function present(GrupoConjuntoMensagem $mensagem, string $timezone): array
    {
        $failedItems = [];

        foreach ($this->resultItems($mensagem) as $item) {
            if ((string) ($item['status'] ?? '') !== 'failed') {
                continue;
            }

            $httpStatus = $this->normalizeHttpStatus($item['http_status'] ?? null);
            $jid = trim((string) ($item['jid'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));

            $failedItems[] = [
                'name' => $name !== '' ? $name : ($jid !== '' ? $jid : 'Grupo não identificado'),
                'jid' => $jid,
                'message' => $this->friendlyMessage($item['error'] ?? null, $httpStatus),
                'http_status' => $httpStatus,
            ];
        }

        $generalHttpStatus = $failedItems[0]['http_status'] ?? null;
        $hasFailure = (string) $mensagem->status === 'failed'
            || (int) $mensagem->failed_count > 0
            || $failedItems !== [];

        return [
            'id' => (int) $mensagem->id,
            'status' => (string) $mensagem->status,
            'status_label' => $this->statusLabel((string) $mensagem->status),
            'sent_count' => (int) $mensagem->sent_count,
            'failed_count' => (int) $mensagem->failed_count,
            'failed_at_label' => $mensagem->failed_at?->copy()->setTimezone($timezone)->format('d/m/Y H:i'),
            'failure' => $hasFailure ? [
                'message' => $this->friendlyMessage($mensagem->error_message, $generalHttpStatus),
                'items' => $failedItems,
            ] : null,
        ];
    }

    public function friendlyMessage(mixed $rawMessage, ?int $httpStatus = null): string
    {
        $raw = trim(is_scalar($rawMessage) ? (string) $rawMessage : '');
        $normalized = Str::lower(Str::ascii($raw));

        if (Str::contains($normalized, [
            'not an admin',
            'not admin',
            'admin required',
            'only admins',
            'permission denied',
            'sem permissao',
        ])) {
            return 'A conexão não tem permissão para executar esta ação no grupo. Confirme que o número conectado é administrador do grupo.';
        }

        if ($httpStatus === 401 || $httpStatus === 403
            || Str::contains($normalized, ['unauthorized', 'forbidden', 'not authorized', 'nao autorizado'])) {
            return 'A conexão não está autorizada para executar esta ação. Verifique a conexão e tente novamente.';
        }

        if ($httpStatus === 404
            || Str::contains($normalized, ['group not found', 'grupo nao encontrado', 'grupo inexistente'])) {
            return 'O grupo não foi encontrado ou não está mais disponível.';
        }

        if (in_array($httpStatus, [408, 504], true)
            || Str::contains($normalized, ['timeout', 'timed out', 'tempo limite'])) {
            return 'A integração demorou demais para responder. Tente novamente em alguns instantes.';
        }

        if (in_array($httpStatus, [400, 409, 422], true)) {
            return 'A integração rejeitou os dados enviados. Revise a ação e tente novamente.';
        }

        if ($httpStatus === 429
            || Str::contains($normalized, ['too many requests', 'rate limit'])) {
            return 'O limite temporário de requisições foi atingido. Tente novamente em alguns instantes.';
        }

        if ($httpStatus !== null && $httpStatus >= 500) {
            return 'A integração do WhatsApp está temporariamente indisponível. Tente novamente mais tarde.';
        }

        if (Str::contains($normalized, ['conexao invalida', 'conexao desconectada', 'connection refused'])) {
            return 'A conexão do WhatsApp está inválida ou desconectada. Verifique a conexão e tente novamente.';
        }

        if (Str::contains($normalized, ['nenhum destinatario', 'nenhum grupo valido'])) {
            return 'Nenhum grupo válido foi encontrado para executar esta ação.';
        }

        if (Str::contains($normalized, ['cooldown', 'backoff'])) {
            return 'A conexão está aguardando para realizar novas ações. Tente novamente em alguns instantes.';
        }

        if (Str::contains($normalized, [
            'acao invalida',
            'texto da mensagem nao informado',
            'tipo de midia invalido',
            'url da midia',
            'titulo do grupo',
            'descricao do grupo',
            'url da imagem',
        ])) {
            return 'Os dados da ação são inválidos. Revise a ação e tente novamente.';
        }

        return self::UNKNOWN_FAILURE_MESSAGE;
    }

    private function resultItems(GrupoConjuntoMensagem $mensagem): array
    {
        $items = data_get($mensagem->result, 'items', []);

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn ($item): bool => is_array($item)));
    }

    private function normalizeHttpStatus(mixed $status): ?int
    {
        if (! is_numeric($status)) {
            return null;
        }

        $status = (int) $status;

        return $status >= 100 && $status <= 599 ? $status : null;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Programada',
            'queued' => 'Na fila',
            'sent' => 'Enviada',
            'failed' => 'Falhou',
            'canceled' => 'Cancelada',
            default => ucfirst($status),
        };
    }
}
