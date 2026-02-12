<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssistantLeadController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $clienteId = $request->filled('cliente_id') ? (int) $request->input('cliente_id') : null;
        $assistantId = $request->filled('assistant_id') ? (int) $request->input('assistant_id') : null;

        $assistantLeadsQuery = AssistantLead::with(['lead.cliente', 'assistant'])->latest();

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

        if (!empty($assistantId)) {
            $assistantLeadsQuery->where('assistant_id', $assistantId);
        }

        $assistantLeads = $assistantLeadsQuery->paginate(25)->withQueryString();

        $clienteIds = AssistantLead::query()
            ->join('cliente_lead', 'cliente_lead.id', '=', 'assistant_lead.lead_id')
            ->whereNotNull('cliente_lead.cliente_id')
            ->distinct()
            ->pluck('cliente_lead.cliente_id');

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

        return view('admin.assistant-lead.index', compact(
            'assistantLeads',
            'search',
            'clienteId',
            'assistantId',
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
