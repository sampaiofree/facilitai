<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Services\OpenAIService;
use App\Support\OpenAIConversationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OpenAIController extends Controller
{
    public function convId(Request $request)
    {
        $data = $this->resolveConversationData($request);

        if ($request->wantsJson()) {
            return $this->jsonConversationResponse($data);
        }

        return view('agencia.openai.conv_id', $data);
    }

    public function conversas(Request $request)
    {
        if ($request->wantsJson()) {
            $data = $this->resolveConversationData($request);
            return $this->jsonConversationResponse($data);
        }

        $data = $this->resolveConversationData($request, false);
        $conexoes = $this->loadAvailableConnections((int) $request->user()->id);
        $selectedConexao = $this->resolveSelectedConnection($request, $data, $conexoes);

        return view('agencia.openai.conversas', array_merge($data, [
            'conexoes' => $conexoes,
            'selectedConexao' => $selectedConexao,
            'sidebarLeads' => $selectedConexao
                ? $this->loadSidebarLeads($selectedConexao, (string) $data['convId'])
                : collect(),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConversationData(Request $request, bool $requireConvId = true): array
    {
        $user = $request->user();
        $convId = trim((string) $request->input('conv_id'));
        $after = (string) $request->input('after');
        $limit = $request->integer('limit');

        $data = [
            'convId' => $convId,
            'result' => null,
            'error' => null,
            'assistantLead' => null,
            'assistantLeadMatchesCount' => 0,
            'items' => [],
            'messages' => [],
            'technicalItems' => [],
            'hasMore' => false,
            'lastId' => null,
            'firstId' => null,
            'object' => null,
            'status' => null,
            'after' => $after !== '' ? $after : null,
            'limit' => $limit,
            'conexao' => null,
        ];

        if ($convId === '') {
            if ($requireConvId) {
                $data['error'] = 'Conv_id nao informado.';
            }

            return $data;
        }

        $request->validate([
            'conv_id' => ['required', 'string'],
        ]);

        $assistantLeadQuery = AssistantLead::query()
            ->where('conv_id', $convId)
            ->whereHas('lead.cliente', fn ($query) => $query->where('user_id', $user->id));

        $data['assistantLeadMatchesCount'] = (clone $assistantLeadQuery)->count();
        $assistantLead = (clone $assistantLeadQuery)
            ->with([
                'assistant',
                'lead.cliente',
                'lead.tags',
                'lead.customFieldValues.customField',
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        $data['assistantLead'] = $assistantLead;

        if (!$assistantLead) {
            $data['error'] = "Conv_id \"{$convId}\" nao encontrado para este usuario.";

            return $data;
        }

        $cliente = $assistantLead->lead?->cliente;
        if (!$cliente) {
            $data['error'] = 'Cliente associado ao conv_id nao encontrado.';

            return $data;
        }

        $conexao = $this->resolveConnectionForAssistantLead($assistantLead);
        $data['conexao'] = $conexao;

        if (!$conexao) {
            $data['error'] = 'Nao ha conexao com credencial disponivel para este cliente.';

            return $data;
        }

        $credential = $conexao->credential;
        if (!$credential || !$credential->token) {
            $data['error'] = 'Credencial vinculada a conexao nao contem token.';

            return $data;
        }

        try {
            $openAi = new OpenAIService($credential->token);
            $query = [];
            if ($after !== '') {
                $query['after'] = $after;
            }
            if ($limit) {
                $query['limit'] = $limit;
            }

            $response = $openAi->getConversationItems($convId, $query);

            if ($response === null) {
                $data['error'] = 'OpenAIService retornou resposta nula.';

                return $data;
            }

            $data['result'] = $response->json();
            $data['status'] = $response->status();

            if (!$response->successful()) {
                $apiMessage = $response->json('error.message') ?? $response->body();
                $data['error'] = 'OpenAI retornou erro (' . $data['status'] . '): ' . $apiMessage;

                return $data;
            }

            $data['items'] = is_array($data['result']['data'] ?? null) ? $data['result']['data'] : [];
            $partitionedItems = OpenAIConversationFormatter::partitionItems($data['items']);
            $data['messages'] = $partitionedItems['messages'];
            $data['technicalItems'] = $partitionedItems['technicalItems'];
            $data['hasMore'] = (bool) ($data['result']['has_more'] ?? false);
            $data['lastId'] = $data['result']['last_id'] ?? null;
            $data['firstId'] = $data['result']['first_id'] ?? null;
            $data['object'] = $data['result']['object'] ?? null;
        } catch (\Throwable $exception) {
            Log::channel('agencia')->error('Erro ao buscar conversa no OpenAI', [
                'conv_id' => $convId,
                'conexao_id' => $conexao->id,
                'error' => $exception->getMessage(),
            ]);
            $data['error'] = 'Falha ao consultar o OpenAI: ' . $exception->getMessage();
        }

        return $data;
    }

    private function resolveConnectionForAssistantLead(AssistantLead $assistantLead): ?Conexao
    {
        $cliente = $assistantLead->lead?->cliente;
        if (!$cliente) {
            return null;
        }

        $baseQuery = $cliente->conexoes()
            ->whereNotNull('assistant_id')
            ->whereNotNull('credential_id')
            ->with(['credential', 'assistant', 'cliente'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        $conexao = (clone $baseQuery)
            ->where('assistant_id', $assistantLead->assistant_id)
            ->first();

        if ($conexao) {
            return $conexao;
        }

        return (clone $baseQuery)->first();
    }

    private function loadAvailableConnections(int $userId)
    {
        return Conexao::query()
            ->with(['assistant:id,name', 'cliente:id,nome'])
            ->whereNotNull('assistant_id')
            ->whereNotNull('cliente_id')
            ->whereNotNull('credential_id')
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $userId))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();
    }

    private function resolveSelectedConnection(Request $request, array $conversationData, $conexoes): ?Conexao
    {
        $resolvedConversationConnection = $conversationData['conexao'] ?? null;
        if ($resolvedConversationConnection instanceof Conexao) {
            $resolved = $conexoes->firstWhere('id', $resolvedConversationConnection->id);
            if ($resolved instanceof Conexao) {
                return $resolved;
            }
        }

        $requestedConnectionId = $request->integer('conexao_id');
        if ($requestedConnectionId) {
            $requested = $conexoes->firstWhere('id', $requestedConnectionId);
            if ($requested instanceof Conexao) {
                return $requested;
            }
        }

        $first = $conexoes->first();

        return $first instanceof Conexao ? $first : null;
    }

    private function loadSidebarLeads(Conexao $conexao, string $activeConvId)
    {
        return ClienteLead::query()
            ->where('cliente_id', (int) $conexao->cliente_id)
            ->whereHas('assistantLeads', function ($query) use ($conexao) {
                $query->where('assistant_id', (int) $conexao->assistant_id)
                    ->whereNotNull('conv_id');
            })
            ->with([
                'assistantLeads' => function ($query) use ($conexao) {
                    $query->where('assistant_id', (int) $conexao->assistant_id)
                        ->whereNotNull('conv_id')
                        ->orderByDesc('updated_at')
                        ->orderByDesc('id');
                },
            ])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(function (ClienteLead $lead) use ($conexao, $activeConvId) {
                $assistantLead = $lead->assistantLeads->first();
                $convId = trim((string) ($assistantLead?->conv_id ?? ''));

                return [
                    'lead_id' => (int) $lead->id,
                    'name' => trim((string) ($lead->name ?? '')) ?: 'Lead sem nome',
                    'phone' => trim((string) ($lead->phone ?? '')) ?: '-',
                    'updated_at_label' => $lead->updated_at?->format('d/m/Y H:i') ?: '-',
                    'conv_id' => $convId,
                    'url' => route('agencia.openai.conversas', [
                        'conexao_id' => $conexao->id,
                        'conv_id' => $convId,
                    ]),
                    'is_active' => $convId !== '' && $convId === $activeConvId,
                ];
            })
            ->values();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function jsonConversationResponse(array $data)
    {
        $statusCode = $data['error'] ? ($data['status'] ?: 400) : 200;

        return response()->json([
            'conv_id' => $data['convId'],
            'data' => $data['items'],
            'has_more' => $data['hasMore'],
            'last_id' => $data['lastId'],
            'first_id' => $data['firstId'],
            'object' => $data['object'],
            'after' => $data['after'],
            'limit' => $data['limit'],
            'status' => $data['status'],
            'error' => $data['error'],
        ], $statusCode);
    }
}
