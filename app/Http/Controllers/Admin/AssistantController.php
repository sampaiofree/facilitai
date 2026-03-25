<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AssistantController extends Controller
{
    public function index(): View
    {
        $assistants = Assistant::query()
            ->with(['user:id,name', 'cliente:id,nome'])
            ->latest('updated_at')
            ->paginate(25);

        return view('admin.assistants.index', [
            'assistants' => $assistants,
        ]);
    }

    public function show(Assistant $assistant): JsonResponse
    {
        $assistant->loadMissing(['user:id,name', 'cliente:id,nome']);

        return response()->json([
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
        ]);
    }
}
