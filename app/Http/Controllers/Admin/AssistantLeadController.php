<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantLead;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssistantLeadController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $assistantLeadsQuery = AssistantLead::with(['lead', 'assistant'])->latest();

        if ($search !== '') {
            $assistantLeadsQuery->whereHas('lead', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $assistantLeads = $assistantLeadsQuery->paginate(25)->withQueryString();

        return view('admin.assistant-lead.index', compact('assistantLeads', 'search'));
    }

    public function destroy(AssistantLead $assistantLead): RedirectResponse
    {
        $assistantLead->delete();

        return redirect()
            ->route('adm.assistant-lead.index')
            ->with('success', 'Registro de AssistantLead removido com sucesso.');
    }
}
