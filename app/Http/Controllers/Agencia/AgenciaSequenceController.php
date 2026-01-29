<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Conexao;
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
        $clients = Cliente::where('user_id', $user->id)->orderBy('nome')->get();
        $tags = Tag::where('user_id', $user->id)->orderBy('name')->get();
        $sequences = Sequence::with(['cliente', 'conexao', 'steps', 'logs.sequenceStep'])
            ->where('user_id', $user->id)
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

        return view('agencia.sequences.index', compact('sequences', 'clients', 'tags', 'sequenceChatsBySequence'));
    }

    public function destroySequenceChat(Request $request, SequenceChat $sequenceChat): RedirectResponse
    {
        $sequence = $sequenceChat->sequence;
        abort_unless($sequence && $sequence->user_id === $request->user()->id, 404);

        $sequenceChat->delete();

        return back()->with('success', 'Registro de SequenceChat removido com sucesso.');
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
        $conexao = Conexao::where('cliente_id', $cliente->id)->findOrFail($data['conexao_id']);

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

        if (!empty($data['sequence_id'])) {
            $sequence = Sequence::where('user_id', $user->id)->findOrFail($data['sequence_id']);
            $sequence->update($payload);
            $message = 'Sequência atualizada com sucesso.';
        } else {
            Sequence::create($payload);
            $message = 'Sequência criada com sucesso.';
        }

        return redirect()->route('agencia.sequences.index')->with('success', $message);
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

        return redirect()->route('agencia.sequences.index')->with('success', 'Etapa criada com sucesso.');
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

        return redirect()->route('agencia.sequences.index')->with('success', 'Etapa atualizada com sucesso.');
    }

    public function conexoes(Cliente $cliente)
    {
        abort_unless($cliente->user_id === auth()->id(), 403);
        return $cliente->conexoes()->select('id', 'name')->orderBy('name')->get();
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

    private function ensureSequenceOwnership(Sequence $sequence, int $userId): void
    {
        abort_unless($sequence->user_id === $userId, 404);
    }
}
