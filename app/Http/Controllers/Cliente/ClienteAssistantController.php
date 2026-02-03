<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\PromptHelpTipo;
use Illuminate\Http\Request;

class ClienteAssistantController extends Controller
{
    public function index(Request $request)
    {
        $cliente = $request->user('client');

        $assistants = Assistant::where('cliente_id', $cliente->id)
            ->orderByDesc('updated_at')
            ->get();

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

        return view('cliente.assistants.index', [
            'assistants' => $assistants,
            'promptHelpTipos' => $promptHelpTipos,
        ]);
    }

    public function update(Request $request, Assistant $assistant)
    {
        $cliente = $request->user('client');
        $this->ensureOwnership($assistant, $cliente->id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
        ]);

        $assistant->name = $data['name'];
        $assistant->instructions = $data['instructions'];
        $assistant->version = ($assistant->version ?? 0) + 1;
        $assistant->save();

        return redirect()
            ->route('cliente.assistant.index')
            ->with('success', 'Assistente atualizado com sucesso.');
    }

    private function ensureOwnership(Assistant $assistant, int $clienteId): void
    {
        if ($assistant->cliente_id !== $clienteId) {
            abort(403);
        }
    }
}
