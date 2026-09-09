<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Agencia\AgenciaGrupoController;
use App\Models\Cliente;
use App\Models\GrupoConjunto;
use App\Models\GrupoConjuntoMensagem;
use App\Support\GrupoConjuntoFailurePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteGrupoController extends AgenciaGrupoController
{
    public function messageStatuses(
        Request $request,
        GrupoConjunto $grupoConjunto,
        GrupoConjuntoFailurePresenter $failurePresenter
    ): JsonResponse {
        $userId = $this->resolveGrupoOwnerUserId($request);
        $clienteScopeId = $this->resolveGrupoClienteScopeId($request);
        $this->ensureConjuntoOwnership($grupoConjunto, $userId, $clienteScopeId);

        $timezone = $this->resolveGrupoTimezone($request);
        $mensagens = GrupoConjuntoMensagem::query()
            ->where('user_id', $userId)
            ->where('grupo_conjunto_id', (int) $grupoConjunto->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $mensagens
                ->map(fn (GrupoConjuntoMensagem $mensagem): array => $failurePresenter->present($mensagem, $timezone))
                ->values(),
            'has_processing' => $mensagens->contains(
                fn (GrupoConjuntoMensagem $mensagem): bool => in_array((string) $mensagem->status, ['queued', 'pending'], true)
            ),
        ])->header('Cache-Control', 'no-store');
    }

    protected function resolveGrupoOwnerUserId(Request $request): int
    {
        return (int) $this->resolveCliente($request)->user_id;
    }

    protected function resolveGrupoClienteScopeId(Request $request): ?int
    {
        return (int) $this->resolveCliente($request)->id;
    }

    protected function resolveGrupoTimezone(Request $request): string
    {
        $cliente = $this->resolveCliente($request);
        $cliente->loadMissing('user');

        if (! $cliente->user) {
            return 'America/Sao_Paulo';
        }

        return $this->scheduledMessageService->resolveTimezoneForUser($cliente->user);
    }

    protected function grupoRoutePrefix(): string
    {
        return 'cliente.grupos';
    }

    protected function grupoViewLayout(): string
    {
        return 'layouts.cliente';
    }

    protected function showGrupoClienteInfo(): bool
    {
        return false;
    }

    protected function showGrupoFailureDetails(): bool
    {
        return true;
    }

    protected function showGroupNameSequence(): bool
    {
        return true;
    }

    private function resolveCliente(Request $request): Cliente
    {
        $cliente = $request->user('client');

        abort_unless($cliente instanceof Cliente, 403);
        abort_unless((int) ($cliente->user_id ?? 0) > 0, 403);
        abort_unless((bool) $cliente->can_access_groups, 403);

        return $cliente;
    }
}
