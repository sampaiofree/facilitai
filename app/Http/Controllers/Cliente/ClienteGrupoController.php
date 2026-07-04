<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Agencia\AgenciaGrupoController;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteGrupoController extends AgenciaGrupoController
{
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

        if (!$cliente->user) {
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

    private function resolveCliente(Request $request): Cliente
    {
        $cliente = $request->user('client');

        abort_unless($cliente instanceof Cliente, 403);
        abort_unless((int) ($cliente->user_id ?? 0) > 0, 403);
        abort_unless((bool) $cliente->can_access_groups, 403);

        return $cliente;
    }
}
