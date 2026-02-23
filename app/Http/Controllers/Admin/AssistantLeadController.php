<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssistantLeadController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;
        $clienteId = $request->filled('cliente_id') ? (int) $request->input('cliente_id') : null;
        $assistantId = $request->filled('assistant_id') ? (int) $request->input('assistant_id') : null;

        $assistantLeadsQuery = AssistantLead::with(['lead.cliente.user', 'assistant'])->latest();

        if ($search !== '') {
            $assistantLeadsQuery->whereHas('lead', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($clienteId)) {
            $assistantLeadsQuery->whereHas('lead', function ($query) use ($clienteId) {
                $query->where('cliente_id', $clienteId);
            });
        }

        if (!empty($userId)) {
            $assistantLeadsQuery->whereHas('lead.cliente', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });
        }

        if (!empty($assistantId)) {
            $assistantLeadsQuery->where('assistant_id', $assistantId);
        }

        $assistantLeads = $assistantLeadsQuery->paginate(25)->withQueryString();

        $clienteIds = AssistantLead::query()
            ->join('cliente_lead', 'cliente_lead.id', '=', 'assistant_lead.lead_id')
            ->whereNotNull('cliente_lead.cliente_id')
            ->distinct()
            ->pluck('cliente_lead.cliente_id');

        $userIds = AssistantLead::query()
            ->join('cliente_lead', 'cliente_lead.id', '=', 'assistant_lead.lead_id')
            ->join('clientes', 'clientes.id', '=', 'cliente_lead.cliente_id')
            ->whereNotNull('clientes.user_id')
            ->distinct()
            ->pluck('clientes.user_id');

        $assistantIds = AssistantLead::query()
            ->whereNotNull('assistant_id')
            ->distinct()
            ->pluck('assistant_id');

        $clientes = Cliente::query()
            ->whereIn('id', $clienteIds)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $assistants = Assistant::query()
            ->whereIn('id', $assistantIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.assistant-lead.index', compact(
            'assistantLeads',
            'search',
            'userId',
            'clienteId',
            'assistantId',
            'users',
            'clientes',
            'assistants'
        ));
    }

    public function destroy(AssistantLead $assistantLead): RedirectResponse
    {
        $assistantLead->delete();

        return redirect()
            ->route('adm.assistant-lead.index')
            ->with('success', 'Registro de AssistantLead removido com sucesso.');
    }
}
