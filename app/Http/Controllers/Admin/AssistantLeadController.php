<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantLead;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssistantLeadController extends Controller
{
    public function index(): View
    {
        $assistantLeads = AssistantLead::with(['lead', 'assistant'])->latest()->get();
        return view('admin.assistant-lead.index', compact('assistantLeads'));
    }

    public function destroy(AssistantLead $assistantLead): RedirectResponse
    {
        $assistantLead->delete();

        return redirect()
            ->route('adm.assistant-lead.index')
            ->with('success', 'Registro de AssistantLead removido com sucesso.');
    }
}
