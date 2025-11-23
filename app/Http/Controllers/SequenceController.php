<?php

namespace App\Http\Controllers;

use App\Models\Sequence;
use App\Models\SequenceStep;
use App\Models\SequenceChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SequenceController extends Controller
{
    public function index()
    {
        $sequences = Auth::user()->sequences()
            ->withCount([
                'steps',
                'chats as chats_em_andamento_count' => fn ($q) => $q->where('status', 'em_andamento'),
                'chats as chats_total_count',
            ])
            ->latest()
            ->paginate(12);
        return view('sequences.index', compact('sequences'));
    }

    public function show(Request $request, Sequence $sequence)
    {
        $this->authorizeSequence($sequence);
        $sequence->load('steps');

        $statusFilter = $request->query('status');
        $passoFilter = $request->query('passo');

        $chatQuery = $sequence->chats()
            ->with(['chat', 'step'])
            ->orderBy('proximo_envio_em', 'asc')
            ->orderBy('id', 'desc');

        if (in_array($statusFilter, ['em_andamento', 'pausada', 'concluida', 'cancelada'])) {
            $chatQuery->where('status', $statusFilter);
        }

        if ($passoFilter) {
            $chatQuery->whereHas('step', fn ($q) => $q->where('ordem', (int) $passoFilter));
        }

        $sequenceChats = $chatQuery->paginate(20)->withQueryString();
        $totalChats = $sequence->chats()->count();

        $resumoStatus = SequenceChat::select('status', DB::raw('count(*) as total'))
            ->where('sequence_id', $sequence->id)
            ->groupBy('status')
            ->pluck('total', 'status');

        $porPasso = [];
        foreach ($sequence->steps as $step) {
            $porPasso[$step->ordem] = SequenceChat::where('sequence_id', $sequence->id)
                ->where('passo_atual_id', $step->id)
                ->where('status', 'em_andamento')
                ->count();
        }

        return view('sequences.show', compact(
            'sequence',
            'sequenceChats',
            'resumoStatus',
            'porPasso',
            'statusFilter',
            'passoFilter',
            'totalChats'
        ));
    }

    public function create()
    {
        $tags = Auth::user()->tags()->orderBy('name')->get(['name']);
        return view('sequences.form', [
            'tags' => $tags,
            'sequence' => null,
            'steps' => [],
            'action' => route('sequences.store'),
            'method' => 'POST',
            'title' => 'Nova sequência',
            'submitLabel' => 'Salvar sequência',
        ]);
    }

    public function store(Request $request)
    {
        $this->normalizeTags($request);
        $validated = $this->validateSequence($request);
        DB::transaction(function () use ($validated) {
            $sequence = Sequence::create([
                'user_id' => Auth::id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true,
                'tags_incluir' => $validated['tags_incluir'] ?? [],
                'tags_excluir' => $validated['tags_excluir'] ?? [],
            ]);

            $this->syncSteps($sequence, $validated['steps'] ?? []);
        });

        return redirect()->route('sequences.index')->with('success', 'Sequência criada.');
    }

    public function edit(Sequence $sequence)
    {
        $this->authorizeSequence($sequence);
        $sequence->load('steps');
        $tags = Auth::user()->tags()->orderBy('name')->get(['name']);
        return view('sequences.form', [
            'tags' => $tags,
            'sequence' => $sequence,
            'steps' => $sequence->steps,
            'action' => route('sequences.update', $sequence),
            'method' => 'PUT',
            'title' => 'Editar sequência',
            'submitLabel' => 'Salvar sequência',
        ]);
    }

    public function update(Request $request, Sequence $sequence)
    {
        $this->authorizeSequence($sequence);
        $this->normalizeTags($request);
        $validated = $this->validateSequence($request);

        DB::transaction(function () use ($sequence, $validated) {
            $sequence->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'active' => $validated['active'] ?? true,
                'tags_incluir' => $validated['tags_incluir'] ?? [],
                'tags_excluir' => $validated['tags_excluir'] ?? [],
            ]);
            $sequence->steps()->delete();
            $this->syncSteps($sequence, $validated['steps'] ?? []);
        });

        return redirect()->route('sequences.index')->with('success', 'Sequência atualizada.');
    }

    public function destroy(Sequence $sequence)
    {
        $this->authorizeSequence($sequence);
        $sequence->delete();

        return redirect()->route('sequences.index')->with('success', 'Sequência removida.');
    }

    public function removeChat(Sequence $sequence, SequenceChat $sequenceChat)
    {
        $this->authorizeSequence($sequence);
        if ($sequenceChat->sequence_id !== $sequence->id) {
            abort(404);
        }
        $sequenceChat->logs()->delete();
        $sequenceChat->delete();

        return redirect()->back()->with('success', 'Chat removido da sequência.');
    }

    private function validateSequence(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'tags_incluir' => ['nullable', 'array'],
            'tags_incluir.*' => ['string', 'max:100'],
            'tags_excluir' => ['nullable', 'array'],
            'tags_excluir.*' => ['string', 'max:100'],
            'steps' => ['nullable', 'array'],
            'steps.*.title' => ['nullable', 'string', 'max:100'],
            'steps.*.ordem' => ['required', 'integer', 'min:1'],
            'steps.*.atraso_tipo' => ['required', 'in:minuto,hora,dia'],
            'steps.*.atraso_valor' => ['required', 'integer', 'min:0'],
            'steps.*.janela_inicio' => ['nullable', 'date_format:H:i'],
            'steps.*.janela_fim' => ['nullable', 'date_format:H:i'],
            'steps.*.dias_semana' => ['nullable', 'array'],
            'steps.*.dias_semana.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'steps.*.prompt' => ['required', 'string'],
            'steps.*.active' => ['nullable', 'boolean'],
        ]);
    }

    private function syncSteps(Sequence $sequence, array $steps): void
    {
        foreach ($steps as $step) {
            SequenceStep::create([
                'sequence_id' => $sequence->id,
                'title' => $step['title'] ?? null,
                'ordem' => $step['ordem'],
                'atraso_tipo' => $step['atraso_tipo'],
                'atraso_valor' => $step['atraso_valor'],
                'janela_inicio' => $step['janela_inicio'] ?? null,
                'janela_fim' => $step['janela_fim'] ?? null,
                'dias_semana' => $step['dias_semana'] ?? null,
                'prompt' => $step['prompt'],
                'active' => $step['active'] ?? true,
            ]);
        }
    }

    private function authorizeSequence(Sequence $sequence): void
    {
        if ($sequence->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function normalizeTags(Request $request): void
    {
        $toArray = function ($value) {
            if (is_array($value)) {
                return array_values(array_filter(array_map(fn ($v) => trim((string)$v), $value)));
            }
            return array_values(array_filter(array_map(fn ($v) => trim($v), explode(',', (string)$value))));
        };

        if ($request->has('tags_incluir')) {
            $request->merge(['tags_incluir' => $toArray($request->input('tags_incluir'))]);
        }

        if ($request->has('tags_excluir')) {
            $request->merge(['tags_excluir' => $toArray($request->input('tags_excluir'))]);
        }
    }
}
