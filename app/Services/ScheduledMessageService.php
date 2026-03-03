<?php

namespace App\Services;

use App\Models\AgencySetting;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\User;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Support\Carbon;

class ScheduledMessageService
{
    public function __construct(
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer
    ) {
    }

    public function resolveTimezoneForUser(User $user): string
    {
        $timezone = AgencySetting::where('user_id', $user->id)->value('timezone');
        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            return $timezone;
        }

        return 'America/Sao_Paulo';
    }

    public function parseScheduledForToUtc(?string $value, string $timezone): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value, $timezone)->setTimezone('UTC');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{ok:bool,message?:string,lead?:ClienteLead,conexao?:Conexao,phone?:string}
     */
    public function resolveDispatchContext(
        ClienteLead $lead,
        int $assistantId,
        ?int $ownerUserId = null,
        ?int $preferredConexaoId = null
    ): array {
        $lead->loadMissing(['cliente', 'assistantLeads']);

        if (!$lead->cliente) {
            return ['ok' => false, 'message' => 'Lead sem cliente vinculado.'];
        }

        if ($ownerUserId !== null && (int) $lead->cliente->user_id !== (int) $ownerUserId) {
            return ['ok' => false, 'message' => 'Lead nao pertence ao usuario autenticado.'];
        }

        if (!$lead->bot_enabled) {
            return ['ok' => false, 'message' => 'Bot desativado para este lead.'];
        }

        $phone = $this->phoneNumberNormalizer->normalizeLeadPhone((string) ($lead->phone ?? ''));
        if ($phone === null) {
            return ['ok' => false, 'message' => 'Lead sem telefone valido.'];
        }

        $hasAssistant = $lead->assistantLeads->contains(fn ($assistantLead) => (int) $assistantLead->assistant_id === $assistantId);
        if (!$hasAssistant) {
            return ['ok' => false, 'message' => 'Assistente nao associado ao lead.'];
        }

        $conexao = $this->resolveConexao($lead->cliente_id, $assistantId, $ownerUserId, $preferredConexaoId);
        if (!$conexao) {
            return ['ok' => false, 'message' => 'Conexao nao encontrada para este assistente.'];
        }

        $conexao->loadMissing(['whatsappApi', 'whatsappCloudAccount']);
        $providerSlug = strtolower((string) ($conexao->whatsappApi?->slug ?? ''));

        if ($providerSlug === 'whatsapp_cloud') {
            $cloudToken = trim((string) ($conexao->whatsappCloudAccount?->access_token ?? ''));
            $legacyToken = trim((string) ($conexao->whatsapp_api_key ?? ''));

            if ($cloudToken === '' && $legacyToken === '') {
                return ['ok' => false, 'message' => 'Conexao cloud sem credenciais validas.'];
            }
        } elseif (trim((string) ($conexao->whatsapp_api_key ?? '')) === '') {
            return ['ok' => false, 'message' => 'Conexao sem whatsapp_api_key valida.'];
        }

        return [
            'ok' => true,
            'lead' => $lead,
            'conexao' => $conexao,
            'phone' => $phone,
        ];
    }

    private function resolveConexao(
        int $clienteId,
        int $assistantId,
        ?int $ownerUserId = null,
        ?int $preferredConexaoId = null
    ): ?Conexao {
        $query = Conexao::query()
            ->where('cliente_id', $clienteId)
            ->where('assistant_id', $assistantId)
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($ownerUserId !== null) {
            $query->whereHas('cliente', fn ($q) => $q->where('user_id', $ownerUserId));
        }

        if ($preferredConexaoId) {
            $preferred = (clone $query)->whereKey($preferredConexaoId)->first();
            if ($preferred) {
                return $preferred;
            }
        }

        return $query->latest('id')->first();
    }
}
