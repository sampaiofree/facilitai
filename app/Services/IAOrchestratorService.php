<?php

namespace App\Services;

use App\DTOs\IAResult;
use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Services\OpenAIOrchestratorService;
use App\Support\LogContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IAOrchestratorService
{
    public function handleMessage(Conexao $conexao, Assistant $assistant, ClienteLead $lead, AssistantLead $assistantLead, array $payload, array $handlers = []): IAResult
    {
        $provider = Str::lower((string) ($conexao->credential?->iaplataforma?->nome ?? 'openai'));
        if ($provider === '' || $provider === 'openai') {
            $openAi = new OpenAIOrchestratorService();
            return $openAi->handle($conexao, $assistant, $lead, $assistantLead, $payload, $handlers);
        }

        Log::channel('ia_orchestrator')->warning('IA provider não suportado.', $this->logContext($payload, $conexao, [
            'provider' => $provider,
        ]));

        return IAResult::error('IA provider não suportado.', $provider);
    }

    private function logContext(array $payload = [], ?Conexao $conexao = null, array $extra = []): array
    {
        return LogContext::merge(
            LogContext::base($payload, $conexao),
            $extra
        );
    }
}
