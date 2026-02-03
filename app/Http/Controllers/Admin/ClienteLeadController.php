<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClienteLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteLeadController extends Controller
{
    /**
     * Display the list of cliente leads.
     */
    public function index(): View
    {
        $leads = ClienteLead::with(['cliente', 'assistantLeads.assistant'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.cliente-lead.index', compact('leads'));
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(ClienteLead $clienteLead): RedirectResponse
    {
        $clienteLead->delete();

        return redirect()
            ->route('adm.cliente-lead.index')
            ->with('success', 'Lead removido com sucesso.');
    }
}
