<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\PromptHelpTipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantController extends Controller
{
    public function index(): View
    {
        $assistants = Assistant::query()
            ->with(['user:id,name', 'cliente:id,nome'])
            ->latest('updated_at')
            ->paginate(25);

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

        return view('admin.assistants.index', [
            'assistants' => $assistants,
            'promptHelpTipos' => $promptHelpTipos,
        ]);
    }

    public function show(Assistant $assistant): JsonResponse
    {
        $assistant->loadMissing(['user:id,name', 'cliente:id,nome']);

        return response()->json($this->buildAssistantPayload($assistant));
    }

    public function update(Request $request, Assistant $assistant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'delay' => ['nullable', 'integer', 'min:0'],
        ]);

        $assistant->name = $data['name'];
        $assistant->instructions = $data['instructions'];
        $assistant->delay = $data['delay'] ?? null;
        $assistant->version = ((int) ($assistant->version ?? 0)) + 1;
        $assistant->save();
        $assistant->loadMissing(['user:id,name', 'cliente:id,nome']);

        return response()->json([
            'message' => 'Assistente atualizado com sucesso.',
            ...$this->buildAssistantPayload($assistant),
        ]);
    }

    private function buildAssistantPayload(Assistant $assistant): array
    {
        return [
            'summary' => [
                'id' => $assistant->id,
                'name' => $assistant->name,
                'user_name' => $assistant->user?->name,
                'cliente_name' => $assistant->cliente?->nome,
                'openai_assistant_id' => $assistant->openai_assistant_id,
                'modelo' => $assistant->modelo,
                'delay' => $assistant->delay,
                'version' => $assistant->version,
                'created_at' => $assistant->created_at?->format('d/m/Y H:i:s'),
                'updated_at' => $assistant->updated_at?->format('d/m/Y H:i:s'),
            ],
            'texts' => [
                'instructions' => $assistant->instructions,
                'systemPrompt' => $assistant->systemPrompt,
                'developerPrompt' => $assistant->developerPrompt,
                'prompt_notificar_adm' => $assistant->prompt_notificar_adm,
                'prompt_buscar_get' => $assistant->prompt_buscar_get,
                'prompt_enviar_media' => $assistant->prompt_enviar_media,
                'prompt_registrar_info_chat' => $assistant->prompt_registrar_info_chat,
                'prompt_gerenciar_agenda' => $assistant->prompt_gerenciar_agenda,
                'prompt_aplicar_tags' => $assistant->prompt_aplicar_tags,
                'prompt_sequencia' => $assistant->prompt_sequencia,
            ],
        ];
    }
}
