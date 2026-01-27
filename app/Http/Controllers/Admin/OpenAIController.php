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
        $result = null;
        $error = null;

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
                                $response = $openAi->getConversationItems($convId);

                                if ($response === null) {
                                    $error = 'OpenAIService retornou resposta nula.';
                                } else {
                                    $result = $response->json();
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

        return view('admin.openai.conv_id', [
            'convId' => $convId,
            'result' => $result,
            'error' => $error,
        ]);
    }
}
