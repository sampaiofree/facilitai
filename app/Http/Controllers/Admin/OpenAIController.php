<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantLead;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OpenAIController extends Controller
{
    public function convId(Request $request)
    {
        $convId = (string) $request->input('conv_id');
        $after = (string) $request->input('after');
        $limit = $request->integer('limit');
        $result = null;
        $error = null;
        $items = [];
        $hasMore = false;
        $lastId = null;
        $firstId = null;
        $object = null;
        $status = null;
        $response = null;

        if ($request->filled('conv_id')) {
            $request->validate([
                'conv_id' => ['required', 'string'],
            ]);

            $assistantLead = AssistantLead::with(['assistant', 'lead.cliente'])
                ->where('conv_id', $convId)
                ->first();

            if (!$assistantLead) {
                $error = "Conv_id \"{$convId}\" não encontrado em assistant_lead.";
            } else {
                $cliente = $assistantLead->lead?->cliente;
                if (!$cliente) {
                    $error = 'Cliente associado ao conv_id não encontrado.';
                } else {
                    $conexao = $cliente->conexoes()
                        ->where('assistant_id', $assistantLead->assistant_id)
                        ->whereNotNull('credential_id')
                        ->with('credential')
                        ->first();

                    if (!$conexao) {
                        $conexao = $cliente->conexoes()
                            ->whereNotNull('credential_id')
                            ->with('credential')
                            ->first();
                    }

                    if (!$conexao) {
                        $error = 'Não há conexão com credencial disponível para este cliente.';
                    } else {
                        $credential = $conexao->credential;
                        if (!$credential || !$credential->token) {
                            $error = 'Credencial vinculada à conexão não contém token.';
                        } else {
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
                                    $error = 'OpenAIService retornou resposta nula.';
                                } else {
                                    $result = $response->json();
                                    $status = $response->status();

                                    if (!$response->successful()) {
                                        $apiMessage = $response->json('error.message') ?? $response->body();
                                        $error = 'OpenAI retornou erro (' . $status . '): ' . $apiMessage;
                                    } else {
                                        $items = is_array($result['data'] ?? null) ? $result['data'] : [];
                                        $hasMore = (bool) ($result['has_more'] ?? false);
                                        $lastId = $result['last_id'] ?? null;
                                        $firstId = $result['first_id'] ?? null;
                                        $object = $result['object'] ?? null;
                                    }
                                }
                            } catch (\Throwable $exception) {
                                Log::channel('admin')->error('Erro ao buscar conversa no OpenAI', [
                                    'conv_id' => $convId,
                                    'conexao_id' => $conexao->id,
                                    'error' => $exception->getMessage(),
                                ]);
                                $error = 'Falha ao consultar o OpenAI: ' . $exception->getMessage();
                            }
                        }
                    }
                }
            }
        }

        if ($request->wantsJson()) {
            $statusCode = $error ? ($status ?: 400) : 200;

            return response()->json([
                'conv_id' => $convId,
                'data' => $items,
                'has_more' => $hasMore,
                'last_id' => $lastId,
                'first_id' => $firstId,
                'object' => $object,
                'after' => $after !== '' ? $after : null,
                'limit' => $limit,
                'status' => $status,
                'error' => $error,
            ], $statusCode);
        }

        return view('admin.openai.conv_id', [
            'convId' => $convId,
            'result' => $result,
            'error' => $error,
            'items' => $items,
            'hasMore' => $hasMore,
            'lastId' => $lastId,
            'firstId' => $firstId,
            'object' => $object,
            'status' => $status,
            'after' => $after !== '' ? $after : null,
            'limit' => $limit,
        ]);
    }
}
