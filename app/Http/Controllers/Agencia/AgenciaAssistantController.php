<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\PromptHelpTipo;
use Illuminate\Http\Request;

class AgenciaAssistantController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $assistants = Assistant::where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();
        $clients = \App\Models\Cliente::where('user_id', $user->id)->orderBy('nome')->get();

        $promptHelpTipos = PromptHelpTipo::with([
            'sections' => function ($query) {
                $query->orderBy('name')->with([
                    'prompts' => function ($promptQuery) {
                        $promptQuery->orderBy('name');
                    },
                ]);
            },
        ])
            ->orderBy('name')
            ->get();

        return view('agencia.assistants.index', [
            'assistants' => $assistants,
            'promptHelpTipos' => $promptHelpTipos,
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);

        Assistant::create([
            'user_id' => $user->id,
            'cliente_id' => $data['cliente_id'] ?? null,
            'name' => $data['name'],
            'instructions' => $data['instructions'],
            'version' => 1,
        ]);

        return redirect()
            ->route('agencia.assistant.index')
            ->with('success', 'Assistente criado com sucesso.');
    }

    public function update(Request $request, Assistant $assistant)
    {
        $this->ensureOwnership($request, $assistant);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
        ]);

        $assistant->name = $data['name'];
        $assistant->instructions = $data['instructions'];
        $assistant->cliente_id = $data['cliente_id'] ?? null;
        $assistant->version = ($assistant->version ?? 0) + 1;
        $assistant->save();

        return redirect()
            ->route('agencia.assistant.index')
            ->with('success', 'Assistente atualizado com sucesso.');
    }

    private function ensureOwnership(Request $request, Assistant $assistant): void
    {
        if ($assistant->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
