<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\PromptHelpTipo;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\SequenceStep;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClienteSequenceController extends Controller
{
    public function index(Request $request)
    {
        $cliente = $request->user('client');

        $clients = Cliente::query()
            ->where('id', $cliente->id)
            ->where('user_id', $cliente->user_id)
            ->orderBy('nome')
            ->get();

        $tags = Tag::query()
            ->where('user_id', $cliente->user_id)
            ->where('cliente_id', $cliente->id)
            ->orderBy('name')
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

        $sequences = Sequence::with(['cliente', 'conexao', 'steps', 'logs.sequenceStep'])
            ->where('user_id', $cliente->user_id)
            ->where('cliente_id', $cliente->id)
            ->latest()
            ->get();

        $sequenceChatsBySequence = [];
        foreach ($sequences as $sequence) {
            $pageParam = (int) $request->input("sequence_chats_page_{$sequence->id}", 1);
            $sequenceChatsBySequence[$sequence->id] = SequenceChat::with('clienteLead')
                ->where('sequence_id', $sequence->id)
                ->orderByDesc('id')
                ->paginate(50, ['*'], "sequence_chats_page_{$sequence->id}", $pageParam)
                ->withQueryString();
        }

        return view('cliente.sequences.index', compact('sequences', 'clients', 'tags', 'promptHelpTipos', 'sequenceChatsBySequence'));
    }

    public function destroySequenceChat(Request $request, SequenceChat $sequenceChat): RedirectResponse
    {
        $cliente = $request->user('client');
        $sequence = $sequenceChat->sequence;

        abort_unless(
            $sequence
            && $sequence->user_id === $cliente->user_id
            && $sequence->cliente_id === $cliente->id,
            404
        );

        $sequenceChat->delete();

        return back()->with('success', 'Registro de SequenceChat removido com sucesso.');
    }

    public function destroySequenceChatsBySequence(Request $request, Sequence $sequence): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureSequenceOwnership($sequence, $cliente->id, $cliente->user_id);

        $deleted = SequenceChat::where('sequence_id', $sequence->id)->delete();

        return back()->with('success', "{$deleted} chat(s) da sequencia removido(s) com sucesso.");
    }

    public function store(Request $request): RedirectResponse
    {
        $cliente = $request->user('client');
        $data = $request->validate([
            'sequence_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cliente_id' => ['required', 'integer', 'in:' . $cliente->id],
            'conexao_id' => ['required', 'integer', 'exists:conexoes,id'],
            'active' => ['nullable', 'boolean'],
            'tags_incluir' => ['nullable', 'array'],
            'tags_excluir' => ['nullable', 'array'],
        ]);

        $conexao = Conexao::query()
            ->where('id', $data['conexao_id'])
            ->where('cliente_id', $cliente->id)
            ->firstOrFail();

        $payload = [
            'user_id' => $cliente->user_id,
            'cliente_id' => $cliente->id,
            'conexao_id' => $conexao->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => $request->boolean('active'),
            'tags_incluir' => $this->normalizeTags($data['tags_incluir'] ?? []),
            'tags_excluir' => $this->normalizeTags($data['tags_excluir'] ?? []),
        ];

        if (!empty($data['sequence_id'])) {
            $sequence = Sequence::query()
                ->where('user_id', $cliente->user_id)
                ->where('cliente_id', $cliente->id)
                ->findOrFail($data['sequence_id']);
            $sequence->update($payload);
            $message = 'Sequencia atualizada com sucesso.';
        } else {
            Sequence::create($payload);
            $message = 'Sequencia criada com sucesso.';
        }

        return redirect()->route('cliente.sequences.index')->with('success', $message);
    }

    public function destroy(Request $request, Sequence $sequence): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureSequenceOwnership($sequence, $cliente->id, $cliente->user_id);

        $sequence->delete();

        return redirect()
            ->route('cliente.sequences.index')
            ->with('success', 'Sequencia removida com sucesso.');
    }

    public function storeStep(Request $request, Sequence $sequence): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureSequenceOwnership($sequence, $cliente->id, $cliente->user_id);

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

        return redirect()->route('cliente.sequences.index')->with('success', 'Etapa criada com sucesso.');
    }

    public function updateStep(Request $request, Sequence $sequence, SequenceStep $step): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureSequenceOwnership($sequence, $cliente->id, $cliente->user_id);
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

        return redirect()->route('cliente.sequences.index')->with('success', 'Etapa atualizada com sucesso.');
    }

    public function destroyStep(Request $request, Sequence $sequence, SequenceStep $step): RedirectResponse
    {
        $cliente = $request->user('client');
        $this->ensureSequenceOwnership($sequence, $cliente->id, $cliente->user_id);
        abort_unless($step->sequence_id === $sequence->id, 404);

        $step->delete();

        return redirect()
            ->route('cliente.sequences.index')
            ->with('success', 'Etapa removida com sucesso.');
    }

    public function conexoes(Cliente $cliente)
    {
        $authCliente = auth('client')->user();
        abort_unless(
            $cliente->id === $authCliente->id && $cliente->user_id === $authCliente->user_id,
            403
        );

        return $cliente->conexoes()->select('id', 'name')->orderBy('name')->get();
    }

    public function sequences(Cliente $cliente)
    {
        $authCliente = auth('client')->user();
        abort_unless(
            $cliente->id === $authCliente->id && $cliente->user_id === $authCliente->user_id,
            403
        );

        return Sequence::with('conexao')
            ->where('user_id', $authCliente->user_id)
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
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function ensureSequenceOwnership(Sequence $sequence, int $clienteId, int $userId): void
    {
        abort_unless($sequence->cliente_id === $clienteId && $sequence->user_id === $userId, 404);
    }
}
