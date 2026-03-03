<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\PromptHelpTipo;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\SequenceStep;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AgenciaSequenceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activeTab = $request->input('tab') === 'chats' ? 'chats' : 'steps';

        $clients = Cliente::where('user_id', $user->id)->orderBy('nome')->get();
        $allowedClientIds = $clients->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedClientIds = collect((array) $request->input('cliente_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && in_array($id, $allowedClientIds, true))
            ->unique()
            ->values()
            ->all();

        $tags = Tag::where('user_id', $user->id)->orderBy('name')->get();
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

        $sequencesQuery = Sequence::query()
            ->select(['id', 'user_id', 'cliente_id', 'name', 'active', 'created_at'])
            ->with(['cliente:id,nome'])
            ->withCount(['steps', 'chats'])
            ->where('user_id', $user->id)
            ->latest();

        if (!empty($selectedClientIds)) {
            $sequencesQuery->whereIn('cliente_id', $selectedClientIds);
        }

        $sequences = $sequencesQuery->get();

        $selectedSequenceId = $request->filled('sequence_id') ? (int) $request->input('sequence_id') : null;
        if (!$selectedSequenceId || !$sequences->contains('id', $selectedSequenceId)) {
            $selectedSequenceId = $sequences->first()?->id;
        }

        $selectedSequence = null;
        $sequenceChatsPaginator = null;
        if ($selectedSequenceId) {
            $selectedSequence = Sequence::with(['cliente', 'conexao', 'steps'])
                ->where('user_id', $user->id)
                ->find($selectedSequenceId);

            if ($selectedSequence) {
                $sequenceChatsPaginator = SequenceChat::with('clienteLead')
                    ->where('sequence_id', $selectedSequence->id)
                    ->orderByDesc('id')
                    ->paginate(50, ['*'], 'sequence_chats_page')
                    ->withQueryString();
            }
        }

        return view('agencia.sequences.index', compact(
            'sequences',
            'clients',
            'tags',
            'promptHelpTipos',
            'selectedSequence',
            'sequenceChatsPaginator',
            'selectedClientIds',
            'activeTab'
        ));
    }

    public function destroySequenceChat(Request $request, SequenceChat $sequenceChat): RedirectResponse
    {
        $sequence = $sequenceChat->sequence;
        abort_unless($sequence && $sequence->user_id === $request->user()->id, 404);

        $sequenceChat->delete();

        return redirect()
            ->route('agencia.sequences.index', $this->stateForRedirect($request, $sequence->id))
            ->with('success', 'Registro de SequenceChat removido com sucesso.');
    }

    public function destroySequenceChatsBySequence(Request $request, Sequence $sequence): RedirectResponse
    {
        $this->ensureSequenceOwnership($sequence, $request->user()->id);

        $deleted = SequenceChat::where('sequence_id', $sequence->id)->delete();

        return redirect()
            ->route('agencia.sequences.index', $this->stateForRedirect($request, $sequence->id))
            ->with('success', "{$deleted} chat(s) da sequência removido(s) com sucesso.");
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'sequence_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'conexao_id' => ['required', 'integer', 'exists:conexoes,id'],
            'active' => ['nullable', 'boolean'],
            'tags_incluir' => ['nullable', 'array'],
            'tags_excluir' => ['nullable', 'array'],
        ]);

        $cliente = Cliente::where('user_id', $user->id)->findOrFail($data['cliente_id']);
        $conexao = Conexao::where('cliente_id', $cliente->id)
            ->where('is_active', true)
            ->findOrFail($data['conexao_id']);

        $payload = [
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'conexao_id' => $conexao->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => $request->boolean('active'),
            'tags_incluir' => $this->normalizeTags($data['tags_incluir'] ?? []),
            'tags_excluir' => $this->normalizeTags($data['tags_excluir'] ?? []),
        ];

        $selectedSequenceId = null;
        if (!empty($data['sequence_id'])) {
            $sequence = Sequence::where('user_id', $user->id)->findOrFail($data['sequence_id']);
            $sequence->update($payload);
            $selectedSequenceId = $sequence->id;
            $message = 'Sequência atualizada com sucesso.';
        } else {
            $createdSequence = Sequence::create($payload);
            $selectedSequenceId = $createdSequence->id;
            $message = 'Sequência criada com sucesso.';
        }

        return redirect()
            ->route('agencia.sequences.index', $this->stateForRedirect($request, $selectedSequenceId))
            ->with('success', $message);
    }

    public function destroy(Request $request, Sequence $sequence): RedirectResponse
    {
        $this->ensureSequenceOwnership($sequence, $request->user()->id);

        $state = $this->stateForRedirect($request);
        $sequence->delete();
        if (isset($state['sequence_id']) && (int) $state['sequence_id'] === (int) $sequence->id) {
            unset($state['sequence_id'], $state['sequence_chats_page']);
        }

        return redirect()
            ->route('agencia.sequences.index', $state)
            ->with('success', 'Sequencia removida com sucesso.');
    }

    public function storeStep(Request $request, Sequence $sequence)
    {
        $this->ensureSequenceOwnership($sequence, $request->user()->id);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'atraso_valor' => ['required', 'integer', 'min:0'],
            'atraso_tipo' => ['required', 'in:minuto,hora,dia'],
            'janela_inicio' => ['nullable', 'date_format:H:i'],
            'janela_fim' => ['nullable', 'date_format:H:i'],
            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['string', 'max:10'],
            'prompt' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $sequence->steps()->create([
            'title' => $data['title'] ?? null,
            'atraso_valor' => $data['atraso_valor'],
            'atraso_tipo' => $data['atraso_tipo'],
            'janela_inicio' => $data['janela_inicio'] ?? null,
            'janela_fim' => $data['janela_fim'] ?? null,
            'dias_semana' => $data['dias_semana'] ?? [],
            'prompt' => $data['prompt'],
            'active' => $request->boolean('active'),
            'ordem' => (int) ($sequence->steps()->max('ordem') ?? 0) + 1,
        ]);

        return redirect()
            ->route('agencia.sequences.index', $this->stateForRedirect($request, $sequence->id))
            ->with('success', 'Etapa criada com sucesso.');
    }

    public function updateStep(Request $request, Sequence $sequence, SequenceStep $step)
    {
        $this->ensureSequenceOwnership($sequence, $request->user()->id);
        abort_unless($step->sequence_id === $sequence->id, 404);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'atraso_valor' => ['required', 'integer', 'min:0'],
            'atraso_tipo' => ['required', 'in:minuto,hora,dia'],
            'janela_inicio' => ['nullable', 'date_format:H:i'],
            'janela_fim' => ['nullable', 'date_format:H:i'],
            'dias_semana' => ['nullable', 'array'],
            'dias_semana.*' => ['string', 'max:10'],
            'prompt' => ['required', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $step->update([
            'title' => $data['title'] ?? null,
            'atraso_valor' => $data['atraso_valor'],
            'atraso_tipo' => $data['atraso_tipo'],
            'janela_inicio' => $data['janela_inicio'] ?? null,
            'janela_fim' => $data['janela_fim'] ?? null,
            'dias_semana' => $data['dias_semana'] ?? [],
            'prompt' => $data['prompt'],
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('agencia.sequences.index', $this->stateForRedirect($request, $sequence->id))
            ->with('success', 'Etapa atualizada com sucesso.');
    }

    public function destroyStep(Request $request, Sequence $sequence, SequenceStep $step): RedirectResponse
    {
        $this->ensureSequenceOwnership($sequence, $request->user()->id);
        abort_unless($step->sequence_id === $sequence->id, 404);

        $step->delete();

        return redirect()
            ->route('agencia.sequences.index', $this->stateForRedirect($request, $sequence->id))
            ->with('success', 'Etapa removida com sucesso.');
    }

    public function conexoes(Cliente $cliente)
    {
        abort_unless($cliente->user_id === auth()->id(), 403);
        return $cliente->conexoes()
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function sequences(Cliente $cliente)
    {
        abort_unless($cliente->user_id === auth()->id(), 403);

        return Sequence::with('conexao')
            ->where('user_id', auth()->id())
            ->where('cliente_id', $cliente->id)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($sequence) => [
                'id' => $sequence->id,
                'name' => $sequence->name,
                'conexao_id' => $sequence->conexao_id,
                'conexao_name' => $sequence->conexao?->name,
            ]);
    }

    private function normalizeTags(array $tags): array
    {
        return collect($tags)
            ->map(fn($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function stateForRedirect(Request $request, ?int $selectedSequenceId = null): array
    {
        $tab = $request->input('tab') === 'chats' ? 'chats' : 'steps';
        $clientIds = collect((array) $request->input('filter_cliente_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $sequenceId = $selectedSequenceId ?: ($request->filled('current_sequence_id') ? (int) $request->input('current_sequence_id') : null);
        $sequenceChatsPage = $request->filled('sequence_chats_page') ? (int) $request->input('sequence_chats_page') : null;
        $state = array_filter([
            'tab' => $tab === 'chats' ? 'chats' : null,
            'sequence_id' => $sequenceId ?: null,
            'sequence_chats_page' => ($sequenceChatsPage && $sequenceChatsPage > 1) ? $sequenceChatsPage : null,
        ], static fn ($value) => $value !== null && $value !== '');

        if (!empty($clientIds)) {
            $state['cliente_ids'] = $clientIds;
        }

        return $state;
    }

    private function ensureSequenceOwnership(Sequence $sequence, int $userId): void
    {
        abort_unless($sequence->user_id === $userId, 404);
    }
}
