<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Tag;
use App\Models\Sequence;
use App\Models\SequenceChat;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $instances = Auth::user()
            ->instances()
            ->with(['defaultAssistant', 'defaultAssistantByOpenAi'])
            ->orderBy('name')
            ->get(['id', 'name', 'default_assistant_id']);
        $assistants = Auth::user()->assistants()->orderBy('name')->get(['id', 'name']);
        $sequences = Auth::user()->sequences()->orderBy('name')->get(['id', 'name', 'active']);
        $tags = Auth::user()->tags()->orderBy('name')->get(['name']);

        $chats = $this->buildChatQuery($request)
            ->paginate(20)
            ->withQueryString();

        $filters = [
            'search' => $request->query('search'),
            'order' => $request->query('order', 'updated_at_desc'),
            'instance_in' => (array) $request->query('instance_in', []),
            'instance_out' => (array) $request->query('instance_out', []),
            'assistant_in' => (array) $request->query('assistant_in', []),
            'assistant_out' => (array) $request->query('assistant_out', []),
            'status_in' => (array) $request->query('status_in', []),
            'status_out' => (array) $request->query('status_out', []),
            'tags_in' => (array) $request->query('tags_in', []),
            'tags_out' => (array) $request->query('tags_out', []),
            'sequences_in' => (array) $request->query('sequences_in', []),
            'sequences_out' => (array) $request->query('sequences_out', []),
        ];

        return view('chats.index', compact('chats', 'filters', 'instances', 'assistants', 'sequences', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'instance_id' => ['required', 'integer', 'exists:instances,id'],
            'contact' => [
                'required',
                'string',
                'max:255',
                Rule::unique('chats')->where(fn ($query) => $query
                    ->where('instance_id', $request->input('instance_id'))
                    ->where('user_id', Auth::id())
                ),
            ],
            'nome' => ['nullable', 'string', 'max:255'],
            'informacoes' => ['nullable', 'string', 'max:1000'],
            'conv_id' => ['nullable', 'string', 'max:255'],
            'aguardando_atendimento' => ['sometimes', 'boolean'],
        ]);

        $instance = Auth::user()
            ->instances()
            ->with(['defaultAssistant', 'defaultAssistantByOpenAi'])
            ->findOrFail($validated['instance_id']);

        $assistantId = $instance->default_assistant_id ?? null;
        if (!$assistantId) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', 'Defina um assistente padrao na instancia selecionada antes de criar o chat.');
        }

        Chat::create([
            'user_id' => Auth::id(),
            'instance_id' => $instance->id,
            'assistant_id' => $assistantId,
            'contact' => $validated['contact'],
            'nome' => $validated['nome'] ?? null,
            'informacoes' => $validated['informacoes'] ?? null,
            'conv_id' => $validated['conv_id'] ?? null,
            'aguardando_atendimento' => $request->boolean('aguardando_atendimento'),
        ]);

        return redirect()->route('chats.index')->with('success', 'Chat criado com sucesso.');
    }

    public function export(Request $request)
    {
        $chats = $this->buildChatQuery($request)->get();
        $filename = 'chats-' . now()->format('YmdHis') . '.csv';

        $response = new StreamedResponse(function () use ($chats) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['conv_id', 'instancia', 'assistente', 'contato', 'nome', 'informacoes', 'aguardando_atendimento']);

            foreach ($chats as $chat) {
                fputcsv($handle, [
                    $chat->conv_id,
                    $chat->instance?->name ?? '—',
                    $chat->assistant?->name ?? $chat->assistente?->name ?? '—',
                    $chat->contact,
                    $chat->nome,
                    $chat->informacoes,
                    $chat->aguardando_atendimento ? 'Sim' : 'Não',
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function update(Request $request, Chat $chat)
    {
        $this->authorizeChatOwnership($chat);

        $validated = $request->validate([
            'nome' => ['nullable', 'string', 'max:255'],
            'informacoes' => ['nullable', 'string', 'max:1000'],
            'aguardando_atendimento' => ['sometimes', 'boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ]);

        $chat->update([
            'nome' => $validated['nome'] ?? $chat->nome,
            'informacoes' => $validated['informacoes'] ?? $chat->informacoes,
            'aguardando_atendimento' => $request->has('aguardando_atendimento'),
        ]);

        if ($request->has('tags')) {
            $names = collect($validated['tags'] ?? [])
                ->map(fn ($t) => trim($t))
                ->filter()
                ->unique();

            $tagIds = [];
            foreach ($names as $name) {
                $tag = Tag::firstOrCreate(
                    ['user_id' => $chat->user_id, 'name' => $name],
                    ['color' => null, 'description' => null]
                );
                $tagIds[] = $tag->id;
            }
            $chat->tags()->sync($tagIds);
        }

        return redirect()->back()->with('success', 'Chat atualizado.');
    }

    public function toggleBot(Request $request, Chat $chat)
    {
        $this->authorizeChatOwnership($chat);

        $chat->update([
            'bot_enabled' => !$chat->bot_enabled,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'bot_enabled' => $chat->bot_enabled,
            ]);
        }

        return redirect()->back()->with('success', 'Status do bot atualizado.');
    }

    public function bulkMarkAttended(Request $request)
    {
        $selected = collect($request->input('selected', []))->filter()->values();

        if ($selected->isEmpty()) {
            return redirect()->back()->with('warning', 'Selecione ao menos um chat.');
        }

        $updated = Auth::user()->chats()
            ->whereIn('id', $selected)
            ->update(['aguardando_atendimento' => false]);

        return redirect()->back()->with('success', "{$updated} chat(s) marcados como atendidos.");
    }

    public function inscreverSequencia(Request $request)
    {
        $sequenceId = $request->input('sequence_id');
        $selected = collect($request->input('selected', []))->filter()->values();

        if (!$sequenceId) {
            return redirect()->back()->with('warning', 'Selecione uma sequência.');
        }

        if ($selected->isEmpty()) {
            return redirect()->back()->with('warning', 'Selecione ao menos um chat.');
        }

        $sequence = Auth::user()->sequences()->where('id', $sequenceId)->where('active', true)->first();
        if (!$sequence) {
            return redirect()->back()->with('warning', 'Sequência não encontrada ou inativa.');
        }

        $chats = Auth::user()->chats()
            ->whereIn('id', $selected)
            ->with('instance')
            ->get();

        $inscritos = 0;
        $ignorados = [];

        foreach ($chats as $chat) {
            if (!$chat->bot_enabled) {
                $ignorados[] = "{$chat->contact} (bot off)";
                continue;
            }

            $existe = SequenceChat::where('sequence_id', $sequence->id)
                ->where('chat_id', $chat->id)
                ->whereIn('status', ['em_andamento', 'concluida', 'pausada'])
                ->exists();

            if ($existe) {
                $ignorados[] = "{$chat->contact} (já inscrito)";
                continue;
            }

            SequenceChat::create([
                'sequence_id' => $sequence->id,
                'chat_id' => $chat->id,
                'status' => 'em_andamento',
                'iniciado_em' => now('America/Sao_Paulo'),
                'proximo_envio_em' => null,
                'criado_por' => 'usuario',
            ]);
            $inscritos++;
        }

        $msg = "{$inscritos} chat(s) inscritos na sequência.";
        if ($ignorados) {
            $msg .= ' Ignorados: ' . implode(', ', $ignorados);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function applyTags(Request $request, Chat $chat)
    {
        $this->authorizeChatOwnership($chat);

        $request->validate([
            'tags' => ['required', 'string'],
        ]);

        $names = collect(explode(',', $request->input('tags')))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique();

        if ($names->isEmpty()) {
            return redirect()->back()->with('warning', 'Nenhuma tag informada.');
        }

        $tagIds = [];
        foreach ($names as $name) {
            $tag = Tag::firstOrCreate(
                ['user_id' => $chat->user_id, 'name' => $name],
                ['color' => null, 'description' => null]
            );
            $tagIds[] = $tag->id;
        }

        $chat->tags()->syncWithoutDetaching($tagIds);

        return redirect()->back()->with('success', 'Tags aplicadas.');
    }

    public function removeTag(Chat $chat, Tag $tag)
    {
        $this->authorizeChatOwnership($chat);

        if ($tag->user_id !== $chat->user_id) {
            abort(403);
        }

        $chat->tags()->detach($tag->id);

        return redirect()->back()->with('success', 'Tag removida.');
    }

    public function destroy(Chat $chat)
    {
        $this->authorizeChatOwnership($chat);

        $chat->delete();

        return redirect()->route('chats.index')->with('success', 'Conversa excluída com sucesso.');
    }

    private function buildChatQuery(Request $request)
    {
        $query = Auth::user()->chats()->with(['instance', 'assistant', 'tags', 'sequenceChats.sequence']);

        if ($search = $request->query('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('contact', 'like', "%{$search}%")
                    ->orWhere('conv_id', 'like', "%{$search}%")
                    ->orWhere('nome', 'like', "%{$search}%");
            });
        }

        $instanceIn = collect($request->query('instance_in', []))->map(fn ($id) => (int) $id)->filter()->unique();
        if ($instanceIn->isNotEmpty()) {
            $query->whereIn('instance_id', $instanceIn);
        }
        $instanceOut = collect($request->query('instance_out', []))->map(fn ($id) => (int) $id)->filter()->unique();
        if ($instanceOut->isNotEmpty()) {
            $query->whereNotIn('instance_id', $instanceOut);
        }

        $assistantIn = collect($request->query('assistant_in', []))->map(fn ($id) => (int) $id)->filter()->unique();
        if ($assistantIn->isNotEmpty()) {
            $query->whereIn('assistant_id', $assistantIn);
        }
        $assistantOut = collect($request->query('assistant_out', []))->map(fn ($id) => (int) $id)->filter()->unique();
        if ($assistantOut->isNotEmpty()) {
            $query->whereNotIn('assistant_id', $assistantOut);
        }

        $statusIn = collect($request->query('status_in', []))
            ->map(fn ($v) => in_array((string) $v, ['1', 'true', 'on'], true) ? 1 : 0)
            ->unique();
        // Manter compatibilidade com campo antigo
        if ($statusIn->isEmpty() && ($aguardando = $request->query('aguardando_atendimento'))) {
            if (in_array($aguardando, ['0', '1'], true)) {
                $statusIn = collect([(int) $aguardando]);
            }
        }
        if ($statusIn->isNotEmpty()) {
            $query->whereIn('aguardando_atendimento', $statusIn);
        }
        $statusOut = collect($request->query('status_out', []))
            ->map(fn ($v) => in_array((string) $v, ['1', 'true', 'on'], true) ? 1 : 0)
            ->unique();
        if ($statusOut->isNotEmpty()) {
            $query->whereNotIn('aguardando_atendimento', $statusOut);
        }

        $tagsIn = collect($request->query('tags_in', []))->map(fn ($t) => trim($t))->filter()->unique();
        foreach ($tagsIn as $tag) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', $tag);
            });
        }
        $tagsOut = collect($request->query('tags_out', []))->map(fn ($t) => trim($t))->filter()->unique();
        if ($tagsOut->isNotEmpty()) {
            $query->whereDoesntHave('tags', function ($q) use ($tagsOut) {
                $q->whereIn('name', $tagsOut);
            });
        }

        $sequencesIn = collect($request->query('sequences_in', []))->map(fn ($id) => (int) $id)->filter()->unique();
        foreach ($sequencesIn as $seqId) {
            $query->whereHas('sequenceChats', function ($q) use ($seqId) {
                $q->where('sequence_id', $seqId)->where('status', 'em_andamento');
            });
        }
        $sequencesOut = collect($request->query('sequences_out', []))->map(fn ($id) => (int) $id)->filter()->unique();
        if ($sequencesOut->isNotEmpty()) {
            $query->whereDoesntHave('sequenceChats', function ($q) use ($sequencesOut) {
                $q->whereIn('sequence_id', $sequencesOut)->where('status', 'em_andamento');
            });
        }

        $sortOption = $request->query('order', 'updated_at_desc');
        $sortMap = [
            'created_at_asc' => ['created_at', 'asc'],
            'created_at_desc' => ['created_at', 'desc'],
            'updated_at_asc' => ['updated_at', 'asc'],
            'updated_at_desc' => ['updated_at', 'desc'],
        ];

        [$column, $direction] = $sortMap[$sortOption] ?? $sortMap['updated_at_desc'];
        $query->orderBy($column, $direction);

        return $query;
    }

    private function authorizeChatOwnership(Chat $chat): void
    {
        if ($chat->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
