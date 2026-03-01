# Sincronização de Contexto de Template Cloud no conv_id

## Objetivo

Quando um template da WhatsApp Cloud é enviado com sucesso para o lead, precisamos registrar esse envio no contexto OpenAI (`conv_id`) para manter histórico conversacional consistente.

Regras principais:

- Não deve gerar resposta da IA.
- Deve apenas adicionar um item na conversation.
- Deve funcionar com e sem `conv_id` existente.

## Arquitetura Implementada

### 1) Ponto de entrada: envio do template

Arquivo: `app/Http/Controllers/Agencia/ClienteLeadController.php`

Após envio bem-sucedido via `sendTemplateUtility(...)`, o controller:

- Atualiza a janela de conversa 24h (já existente).
- Enfileira `SyncCloudTemplateContextJob` com os dados necessários para sync.

Importante:

- O sync é assíncrono e não bloqueia o envio ao WhatsApp.
- Se o sync falhar, o usuário ainda recebe sucesso do envio do template.

No envio em massa, o payload do job pode incluir também:

- `assistant_context_instructions` (opcional): instruções adicionais que devem ser persistidas no contexto do `conv_id`.

### 2) Job dedicado

Arquivo: `app/Jobs/SyncCloudTemplateContextJob.php`

Responsabilidades:

- Receber payload do template enviado.
- Chamar o service de sincronização.
- Aplicar retries com backoff para falhas transitórias.
- Garantir unicidade de processamento por `meta_message_id` (quando disponível) ou hash do payload.

### 3) Service dedicado

Arquivo: `app/Services/WhatsappCloudTemplateContextSyncService.php`

Responsabilidades:

- Validar e resolver conexão, lead, assistente e template.
- Garantir idempotência (não duplicar o mesmo registro no contexto).
- Garantir `AssistantLead` para o par `lead + assistant`.
- Resolver/garantir `conv_id`.
- Renderizar o conteúdo do template com variáveis.
- Inserir item no OpenAI via `createItems(...)`.
- Nunca chamar `createResponse(...)`.

## Fluxo com os 2 cenários de conv_id

### Cenário A: já existe `conv_id`

1. Recupera `AssistantLead`.
2. Usa `assistant_lead.conv_id`.
3. Envia item para `POST /conversations/{conv_id}/items` (`createItems`).
4. Finaliza sync.

### Cenário B: não existe `conv_id`

1. Recupera/cria `AssistantLead`.
2. Cria nova conversation via `POST /conversations` (`createConversation`).
3. Salva `conv_id` no `AssistantLead`.
4. Envia item com `createItems`.

### Cenário especial: conv_id inválido (conversation not found)

Se `createItems` retornar “conversation not found”:

1. Zera `assistant_lead.conv_id`.
2. Recria conversation.
3. Reenvia `createItems` uma única vez.

## Formato do item adicionado ao contexto

O service monta um texto de auditoria/contexto contendo:

- Metadados do envio (lead, template, idioma, categoria, horário, `meta_message_id` quando houver).
- Instruções adicionais para o assistente (quando informadas na campanha em massa).
- Corpo do template com variáveis substituídas.
- Rodapé (se houver).
- Botões (se houver), inclusive URL renderizada.

Esse texto é incluído como item `message` com role `system`.

## Idempotência

Para evitar duplicidade:

- Chave primária: `meta_message_id` da Meta (quando existe).
- Fallback: hash de (`conexao_id`, `cliente_lead_id`, `template_id`, `sent_at`, `template_variables`).

Estratégia:

- Lock curto (`Cache::add`) para evitar concorrência simultânea.
- Flag de “já sincronizado” com TTL longo (30 dias).

## Tratamento de falhas

### Falhas não bloqueantes (sync ignorado)

- Conexão inexistente.
- Lead inexistente.
- Assistente ausente na conexão.
- Provider IA diferente de OpenAI.
- Token OpenAI inválido/ausente.

Nesses casos:

- Envio do template já foi feito.
- Apenas loga e encerra o sync.

### Falhas transitórias (retry)

Em chamadas OpenAI:

- Timeout/sem resposta.
- HTTP 408/429/5xx.

Nesses casos:

- Job lança exceção.
- Fila aplica retry/backoff.

## Preparação para envio em massa (futuro)

A solução já está pronta para escalar para massa porque:

- O sync está isolado em job/service.
- É idempotente por mensagem.
- Não depende do `ProcessIncomingMessageJob`.

No envio em massa, basta:

1. Enviar N templates.
2. Enfileirar N `SyncCloudTemplateContextJob` (um por envio confirmado).

## Resumo de decisão técnica

- Não reutilizar `ProcessIncomingMessageJob`.
- Criar fluxo dedicado para “append de contexto sem resposta”.
- Tornar resiliente (retry) e seguro (idempotência).
- Não acoplar sucesso do envio de template ao sucesso do sync de contexto.
