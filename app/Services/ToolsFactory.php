<?php

namespace App\Services;

class ToolsFactory
{
    public static function fromSystemPrompt(?string $systemPrompt): array
    {
        $systemPrompt = (string) ($systemPrompt ?? '');
        if ($systemPrompt === '') {
            return [];
        }

        $tools = [];

        if (str_contains($systemPrompt, 'notificar_adm')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'notificar_adm',
                'description' => <<<TXT
Envia um alerta para um administrador humano com um resumo do caso.
Use para escalar atendimentos que precisam de intervenção humana.
Parâmetro obrigatório: mensagem (texto curto e objetivo com o motivo e o contexto).
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'numeros_telefone' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Lista de números de telefone dos administradores.',
                        ],
                        'mensagem' => [
                            'type' => 'string',
                            'description' => 'A mensagem a ser enviada para os administradores.',
                        ],
                    ],
                    'required' => ['numeros_telefone', 'mensagem'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if (str_contains($systemPrompt, 'enviar_media')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'enviar_media',
                'description' => <<<TXT
Use **somente** para enviar um audio, PDF, imagem ou vídeo **já pronto e hospedado publicamente**, **como resposta final visual ao usuário**.
- **Não use** esta ferramenta para criar, gerar, sugerir ou buscar imagens.
- **Não use** esta ferramenta apenas porque o usuário mencionou algo visual.
- **Não use** esta ferramenta para links do youtube.
- Use **apenas** se o assistente precisar realmente **enviar um link de audio/imagem/vídeo/pdf pronto**, como parte da mensagem final enviada ao WhatsApp ou à interface do usuário.
- O conteúdo deve ser **acessível publicamente por URL**.                
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'A URL do áudio, imagem, vídeo ou PDF que será enviada.',
                        ],
                    ],
                    'required' => ['url'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if (str_contains($systemPrompt, 'enviar_post')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'enviar_post',
                'description' => <<<TXT
Use esta ferramenta quando precisar enviar um evento da conversa para um serviço externo via webhook.
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'event' => [
                            'type' => 'string',
                            'description' => 'Tipo do evento disparado pelo agente',
                        ],
                        'url' => [
                            'type' => 'string',
                            'description' => 'Endpoint do webhook',
                        ],
                        'payload' => [
                            'type' => 'object',
                            'description' => 'Dados estruturados do evento',
                        ],
                    ],
                    'required' => ['event', 'url', 'payload'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if (str_contains($systemPrompt, 'buscar_get')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'buscar_get',
                'description' => <<<TXT
Faz uma requisição HTTP GET para a URL informada e retorna o conteúdo (texto/HTML/JSON) para consulta. Use apenas quando houver uma URL específica a ser lida.
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'A URL completa da fonte da informação.',
                        ],
                    ],
                    'required' => ['url'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if (str_contains($systemPrompt, 'registrar_info_chat')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'registrar_info_chat',
                'description' => <<<TXT
Registra ou atualiza informações do chat/lead no sistema interno (ex.: nome, observações, status de atendimento humano).
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'nome' => [
                            'type' => 'string',
                            'description' => 'Nome do cliente ou contato identificado.',
                        ],
                        'informacoes' => [
                            'type' => 'string',
                            'description' => 'Informações adicionais sobre o atendimento.',
                        ],
                        'aguardando_atendimento' => [
                            'type' => 'boolean',
                            'description' => 'Marca se aguarda atendimento humano.',
                        ],
                    ],
                    'required' => ['nome', 'informacoes'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if (str_contains($systemPrompt, 'aplicar_tags')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'aplicar_tags',
                'description' => <<<TXT
Aplica tags existentes ao chat atual para classificação/etapa do atendimento.
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Lista de nomes de tags a aplicar.',
                        ],
                    ],
                    'required' => ['tags'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if (str_contains($systemPrompt, 'inscrever_sequencia')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'inscrever_sequencia',
                'description' => <<<TXT
Inscreve o chat atual em uma sequência automática existente identificada por `sequence_id`.
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'sequence_id' => [
                            'type' => 'integer',
                            'description' => 'ID da sequência a inscrever.',
                        ],
                    ],
                    'required' => ['sequence_id'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if (str_contains($systemPrompt, 'gerenciar_agenda')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'gerenciar_agenda',
                'description' => <<<TXT
Gerencia agendamentos do chat atual (consultar, agendar, cancelar, alterar) apenas para o próprio cliente desta conversa, usando data/hora explícitas informadas pelo usuário ou presentes no histórico. Não crie, cancele ou altere eventos fora deste contexto.
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'acao' => [
                            'type' => 'string',
                            'enum' => ['consultar', 'agendar', 'cancelar', 'alterar'],
                            'description' => 'Tipo de operação desejada na agenda.',
                        ],
                        'agenda_id' => [
                            'type' => 'integer',
                            'description' => 'ID da agenda a ser usada (opcional; se não vier, usar a agenda padrão da instância).',
                        ],
                        'mes' => [
                            'type' => 'integer',
                            'minimum' => 1,
                            'maximum' => 12,
                            'description' => 'Número do mês (1 a 12). Se não informado, usar mês atual.',
                        ],
                        'data_inicio' => [
                            'type' => 'string',
                            'description' => 'Data inicial (YYYY-MM-DD) para consulta em intervalo curto.',
                        ],
                        'data_fim' => [
                            'type' => 'string',
                            'description' => 'Data final (YYYY-MM-DD) para consulta em intervalo curto.',
                        ],
                        'horario' => [
                            'type' => 'string',
                            'description' => 'Horário alvo no formato YYYY-MM-DD HH:mm (usado para agendar/alterar/cancelar).',
                        ],
                        'horario_antigo' => [
                            'type' => 'string',
                            'description' => 'Horário original a ser alterado/cancelado (YYYY-MM-DD HH:mm).',
                        ],
                        'duracao_minutos' => [
                            'type' => 'integer',
                            'description' => 'Duração do serviço em minutos (ex.: 45).',
                        ],
                        'telefone' => [
                            'type' => 'string',
                            'description' => 'Telefone do cliente (usado apenas ao agendar).',
                        ],
                        'nome' => [
                            'type' => 'string',
                            'description' => 'Nome do cliente (usado apenas ao agendar).',
                        ],
                        'disponibilidade_id' => [
                            'type' => 'integer',
                            'description' => 'ID da disponibilidade (apenas se já tiver do histórico; não peça ao usuário).',
                        ],
                        'nova_disponibilidade_id' => [
                            'type' => 'integer',
                            'description' => 'ID da nova disponibilidade (apenas se já tiver do histórico; não peça ao usuário).',
                        ],
                    ],
                    'required' => ['acao'],
                    'additionalProperties' => false,
                ],
                'strict' => false,
            ];
        }

        return $tools;
    }
}
