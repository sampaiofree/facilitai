<?php

namespace App\Http\Controllers;

use App\Services\MassSendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MassCampaign;
use App\Models\Chat;

class MassSendController extends Controller
{
    protected $service;

    public function __construct(MassSendService $service)
    {
        $this->service = $service;
    }

    /**
     * Exibe o formulário de criação de campanha
     */
    public function index()
    {
        $instances = Auth::user()->instances ?? [];
        $tags = Auth::user()->tags()->orderBy('name')->get(['id', 'name']);
        $sequences = Auth::user()->sequences()->orderBy('name')->get(['id', 'name']);

        return view('mass.index', compact('instances', 'tags', 'sequences')); 
    }

    /**
     * Processa o envio da campanha
     */
    public function store(Request $request)
    {
        $request->validate([
            'instance_id' => 'required|integer|exists:instances,id',
            'tipo_envio' => 'required|in:texto,audio',
            'mensagem' => 'required_if:tipo_envio,texto|string|nullable',
            'intervalo_segundos' => 'required|integer|min:2|max:900',
            'tags' => 'array',
            'tags.*' => 'string',
            'sequences' => 'array',
            'sequences.*' => 'integer',
            'tags_mode' => 'in:any,all',
            'sequences_mode' => 'in:any,all',
        ]);

        $instance = Auth::user()
            ->instances()
            ->findOrFail($request->integer('instance_id'));

        $dados = [
            'instance_id' => $instance->id,
            'nome' => $request->nome ?? null,
            'tipo_envio' => $request->tipo_envio,
            'usar_ia' => $request->boolean('usar_ia'),
            'mensagem' => $request->mensagem,
            'intervalo_segundos' => $request->intervalo_segundos,
        ];

        $chatsQuery = $this->buildChatQuery($request, $instance->id);

        if (!$chatsQuery->exists()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', 'Nenhum chat encontrado com os filtros selecionados.');
        }

        $chats = $chatsQuery->select('id', 'contact')->cursor();

        try {
            $campanha = $this->service->criarCampanha($dados, $chats); 
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', 'Nenhum contato válido para disparo.');
        }

        return redirect()->route('mass.index')->with('success', "Campanha criada e iniciada com sucesso! ({$campanha->total_contatos} contatos enfileirados)");
    }

    public function historico()
    {
        $campanhas = MassCampaign::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('mass.historico', compact('campanhas'));
    }

    public function show($id)
    {
        $campanha = MassCampaign::with(['contatos.chat'])->findOrFail($id);

        abort_unless($campanha->user_id === Auth::id(), 403);

        return view('mass.show', compact('campanha'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'instance_id' => 'required|integer|exists:instances,id',
            'tags' => 'array',
            'tags.*' => 'string',
            'sequences' => 'array',
            'sequences.*' => 'integer',
            'tags_mode' => 'in:any,all',
            'sequences_mode' => 'in:any,all',
            'with_list' => 'sometimes|boolean',
            'limit' => 'sometimes|integer|min:1|max:200',
            'offset' => 'sometimes|integer|min:0',
        ]);

        $instance = Auth::user()
            ->instances()
            ->findOrFail($request->integer('instance_id'));

        $query = $this->buildChatQuery($request, $instance->id)
            ->select('id', 'contact', 'nome', 'conv_id')
            ->orderBy('id');

        $withList = $request->boolean('with_list');
        $limit = min(max((int) $request->query('limit', 100), 1), 200);
        $offset = max(0, (int) $request->query('offset', 0));

        $total = 0;
        $invalid = 0;
        $items = [];
        $seen = [];

        $query->chunkById(500, function ($chats) use (&$total, &$invalid, &$items, &$seen, $withList, $limit, $offset) {
            foreach ($chats as $chat) {
                $numero = $this->normalizePhone($chat->contact);

                if (!$numero || strlen($numero) < 10) {
                    $invalid++;
                    continue;
                }

                if (isset($seen[$numero])) {
                    continue;
                }

                $seen[$numero] = true;
                $total++;

                if ($withList && $total > $offset && count($items) < $limit) {
                    $items[] = [
                        'id' => $chat->id,
                        'nome' => $chat->nome ?? null,
                        'contact' => $chat->contact,
                        'conv_id' => $chat->conv_id ?? null,
                    ];
                }

            }
        });

        return response()->json([
            'total' => $total,
            'invalid' => $invalid,
            'offset' => $offset,
            'limit' => $limit,
            'items' => $withList ? $items : [],
        ]);
    }

    protected function buildChatQuery(Request $request, int $instanceId)
    {
        $query = Chat::query()
            ->where('user_id', Auth::id())
            ->where('instance_id', $instanceId)
            ->whereNotNull('contact');

        $tags = collect($request->input('tags', []))
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->unique();

        $tagsMode = $request->input('tags_mode', 'any') === 'all' ? 'all' : 'any';
        if ($tags->isNotEmpty()) {
            if ($tagsMode === 'all') {
                foreach ($tags as $tag) {
                    $query->whereHas('tags', fn ($q) => $q->where('name', $tag));
                }
            } else {
                $query->whereHas('tags', fn ($q) => $q->whereIn('name', $tags));
            }
        }

        $sequences = collect($request->input('sequences', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique();

        $sequencesMode = $request->input('sequences_mode', 'any') === 'all' ? 'all' : 'any';
        if ($sequences->isNotEmpty()) {
            if ($sequencesMode === 'all') {
                foreach ($sequences as $seqId) {
                    $query->whereHas('sequenceChats', function ($q) use ($seqId) {
                        $q->where('sequence_id', $seqId)->where('status', 'em_andamento');
                    });
                }
            } else {
                $query->whereHas('sequenceChats', function ($q) use ($sequences) {
                    $q->whereIn('sequence_id', $sequences)->where('status', 'em_andamento');
                });
            }
        }

        return $query;
    }

    protected function normalizePhone(string $numero): string
    {
        $numero = preg_replace('/\D/', '', $numero);

        if (strlen($numero) <= 11) {
            $numero = '55' . $numero;
        }

        return $numero;
    }


}
