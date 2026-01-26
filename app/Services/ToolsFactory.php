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
Use esta ferramenta **somente em casos excepcionais** onde a conversa exige **intervenção humana imediata**.

**Objetivo:** enviar uma notificação a um administrador humano quando a IA não puder seguir o atendimento de forma segura ou apropriada.

**Regras de uso:**
- ✅ Use **apenas** se:
- houver **erro técnico grave** (ex: falha em ferramentas, dados ausentes, exceções);
- o usuário **solicitar explicitamente falar com um humano**;
- for detectado um **assunto sensível** (reclamação, problema grave, pagamento não confirmado, suporte avançado).
- ⚠️ **Não use** esta ferramenta apenas porque você está em dúvida sobre a resposta.
- ⚠️ **Não use** para enviar atualizações rotineiras, mensagens informativas ou notificações comuns.
- ⚠️ **Não use** automaticamente ao final da conversa.
- ✅ Sempre inclua uma mensagem clara explicando **o motivo do alerta** no campo `mensagem`.
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
Use **somente** para enviar um audio, PDF, imagem ou vídeo **já pronto e hospedado publicamente**,
**como resposta final visual ao usuário**.

- ⚠️ **Não use** esta ferramenta para criar, gerar, sugerir ou buscar imagens.
- ⚠️ **Não use** esta ferramenta apenas porque o usuário mencionou algo visual.
- ✅ Use **apenas** se o assistente precisar realmente **enviar um link de imagem/vídeo pronto**,
como parte da mensagem final enviada ao WhatsApp ou à interface do usuário.
- O conteúdo deve ser **acessível publicamente por URL**.
TXT,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'A URL da imagem ou vídeo que será enviada. Verifique se a URL é de uma imagem ou vídeo acessível publicamente.',
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
Use esta ferramenta **somente quando precisar obter informações reais e atualizadas de uma URL pública e confiável**.

**Objetivo:** fazer uma requisição GET simples para ler o conteúdo de uma página ou API e usar as informações obtidas na resposta ao usuário.

**Regras de uso:**
- ✅ Use **apenas** se a pergunta do usuário depender de dados externos (ex: “qual o valor atual do dólar?”, “o que diz essa notícia?”).
- ⚠️ **Não use** se a informação puder ser respondida com o próprio conhecimento do modelo.
- ⚠️ **Não use** para sites genéricos, buscas no Google, ou páginas sem URL específica fornecida.
- ⚠️ **Não use** para gerar, criar, ou adivinhar conteúdo.
- ✅ Após obter os dados, **resuma e explique de forma simples** ao usuário.
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
Use esta ferramenta quando precisar **registrar informações sobre o cliente ou o atendimento** no sistema interno.

**Objetivo:** salvar ou atualizar os dados do chat atual, incluindo nome, informações complementares e status de atendimento.

**Regras de uso:**
- ✅ Use quando o usuário informar dados úteis (ex: nome, e-mail, produto de interesse, etc.).
- ✅ Use se quiser marcar o chat como "aguardando atendimento humano".
- ⚠️ Não use para mensagens comuns, respostas de texto ou confirmação simples.
- ⚠️ Só use uma vez por interação, com dados claros e estruturados.

Campos aceitos:
- `nome`: nome da pessoa (string)
- `informacoes`: texto livre (ex: “interessado no plano empresarial”)
- `aguardando_atendimento`: booleano (true se precisar de atendimento humano)
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
Aplique tags existentes ao chat atual para classificar o atendimento.
- Use apenas tags que já existam (informadas no contexto/prompt).
- Não crie novas tags e não peça IDs.
- Se não houver tags para aplicar, não chame esta ferramenta.
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
Inscreva o chat atual em uma sequência de mensagens automáticas.
- Sempre use um ID de sequência existente.
- Não reinscreva se já estiver na sequência.
- Respeite as regras de tags configuradas na sequência (aplicadas pelo scheduler).
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
Use esta ferramenta para **consultar, agendar, cancelar ou alterar horários** na agenda interna.

* Sempre que falarem de horários/agendamentos, chame esta tool.  
* **Nunca peça ou mostre IDs**. Envie horário natural (`horario`) e duração (`duracao_minutos`). IDs são só fallback interno.  
* Mostre horários assim: “Quarta, 21/02 — 15h00–15h30”.  
* Se o usuário não disser mês, use o mês atual. Se preciso, consulte por um intervalo curto (`data_inicio`/`data_fim`).  
* Para agendar/alterar, envie o horário exato e a duração do serviço (vem do contexto/prompt).  
* Para cancelar/alterar, use o horário original pelo histórico; não peça ID ao usuário.

Ações suportadas: consultar, agendar, cancelar, alterar.
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
