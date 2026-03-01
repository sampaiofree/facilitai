# Envio em Massa - WhatsApp Cloud

## Objetivo

Implementar campanhas de envio em massa na rota `agencia/whatsapp-api-cloud`, usando modelos aprovados da WhatsApp Cloud API.

O fluxo cobre:

- criação de campanha por cliente
- segmentação opcional por tags do cliente (ou tags globais)
- seleção de conexão Cloud do cliente
- seleção de template aprovado da conexão/conta
- envio imediato ou programado
- limite de 10.000 leads por campanha
- acompanhamento de progresso por item (lead)
- sincronização de contexto no `conv_id` após cada envio com sucesso

## Onde foi implementado

### Back-end

- Controller principal:
  - `app/Http/Controllers/Agencia/WhatsappCloudController.php`
    - `index()`: agora carrega dados da aba de campanhas
    - `campaignLeadCount()`: calcula leads elegíveis por cliente + tags (AJAX)
    - `storeCampaign()`: cria campanha + itens e dispara job
    - `cancelCampaign()`: cancela campanha ativa
- Serviço reutilizável de envio de template:
  - `app/Services/WhatsappCloudTemplateSendService.php`
- Jobs da campanha:
  - `app/Jobs/DispatchWhatsappCloudCampaignJob.php`
  - `app/Jobs/SendWhatsappCloudCampaignItemJob.php`

### Front-end

- Tela da Cloud API:
  - `resources/views/agencia/whatsapp-api-cloud/index.blade.php`
    - nova aba `Envio em massa`
    - tabela de campanhas
    - modal `Nova campanha`
    - preview do template no modal
    - lógica JS de filtro dependente:
      - cliente -> tags
      - cliente -> conexão
      - conexão -> template

### Rotas

- `routes/web.php`
  - `GET agencia/whatsapp-api-cloud/campanhas/leads-elegiveis` (`agencia.whatsapp-cloud.campaigns.lead-count`)
  - `POST agencia/whatsapp-api-cloud/campanhas` (`agencia.whatsapp-cloud.campaigns.store`)
  - `PATCH agencia/whatsapp-api-cloud/campanhas/{campaign}/cancelar` (`agencia.whatsapp-cloud.campaigns.cancel`)

## Estrutura de dados

Migration:

- `database/migrations/2026_03_03_030000_create_whatsapp_cloud_campaigns_tables.php`

Tabelas:

### `whatsapp_cloud_campaigns`

Guarda o cabeçalho da campanha:

- dono (`user_id`)
- cliente/conexão/conta/template selecionados
- `mode`: `immediate` ou `scheduled`
- `status`: `draft`, `scheduled`, `running`, `completed`, `failed`, `canceled`
- `scheduled_for` (UTC)
- contadores agregados:
  - `total_leads`
  - `queued_count`
  - `sent_count`
  - `failed_count`
  - `skipped_count`
- metadados:
  - `settings` (ex.: `interval_seconds`, `assistant_context_instructions`)
  - `filter_payload`
  - `last_error`

### `whatsapp_cloud_campaign_items`

Guarda 1 item por lead da campanha:

- `whatsapp_cloud_campaign_id`
- `cliente_lead_id`
- `phone` (snapshot)
- `status`: `pending`, `queued`, `sent`, `failed`, `skipped`, `canceled`
- `attempts`
- `meta_message_id`
- `resolved_variables`
- `rendered_message`
- `meta_response`
- `error_message`

Regras:

- `unique (campaign_id, lead_id)` para impedir duplicidade do mesmo lead na mesma campanha
- `idempotency_key` único para proteção adicional de reprocessamento

## Modelos criados

- `app/Models/WhatsappCloudCampaign.php`
- `app/Models/WhatsappCloudCampaignItem.php`

Relações também foram adicionadas em:

- `User`
- `Cliente`
- `Conexao`
- `ClienteLead`
- `WhatsappCloudAccount`
- `WhatsappCloudTemplate`

## Fluxo de criação da campanha

1. Usuário abre a aba `Envio em massa` e clica em `Nova campanha`.
2. No modal:
   - escolhe cliente
   - opcionalmente escolhe uma ou mais tags
   - escolhe conexão Cloud do cliente
   - escolhe template aprovado compatível com a conta/conexão
   - opcionalmente informa instruções para o assistente (persistidas no `conv_id`)
   - define envio imediato ou programado
   - define intervalo entre mensagens (segundos)
3. Back-end valida:
   - ownership do usuário
   - conexão pertence ao cliente
   - conexão Cloud com conta vinculada
   - template aprovado (`APPROVED`/`ACTIVE`)
   - template compatível com conta/conexão
   - tags válidas para o cliente
   - existência de leads com telefone após filtro
   - limite máximo de 10.000 leads por campanha
4. Cria registro em `whatsapp_cloud_campaigns`.
5. Cria itens (`pending`) em `whatsapp_cloud_campaign_items` para os leads elegíveis:
   - sem tag: todos os leads do cliente
   - com tag: leads do cliente que possuam pelo menos uma das tags selecionadas
6. Dispara `DispatchWhatsappCloudCampaignJob`:
   - imediato: sem delay
   - programado: com delay até `scheduled_for`

## Fluxo de execução

### Job 1: `DispatchWhatsappCloudCampaignJob`

- Marca campanha como `running` (quando aplicável).
- Move itens `pending` para `queued`.
- Enfileira `SendWhatsappCloudCampaignItemJob` para cada item, respeitando `interval_seconds`.

### Job 2: `SendWhatsappCloudCampaignItemJob`

- Carrega item + campanha + conexão + template + lead.
- Usa `WhatsappCloudTemplateSendService::sendToLead()` para envio.
- Atualiza item:
  - `sent` em sucesso
  - `failed` em erro de envio/regra
  - `skipped` para casos toleráveis (telefone inválido/variável faltante)
- Incrementa contadores da campanha.
- Quando não há mais `pending/queued`, finaliza campanha (`completed`/`failed`).
- Em sucesso:
  - atualiza janela de conversa Cloud (`touchOutbound`)
  - enfileira `SyncCloudTemplateContextJob` para escrever o envio no contexto OpenAI (`conv_id`)

## Reuso do envio individual

O envio de template no modal de conversas também foi ligado ao novo serviço:

- `app/Http/Controllers/Agencia/ClienteLeadController.php`
  - `sendCloudTemplateMessage()` agora usa `WhatsappCloudTemplateSendService`

Isso garante consistência entre:

- envio único
- envio em massa

## Regras de variáveis

Resolução no serviço:

1. valor manual informado
2. valor do campo personalizado no lead
3. fallback de lead (`name`, `phone`, `info`, etc.)
4. `sample_value` do campo personalizado

Se variáveis obrigatórias não resolverem:

- envio único: retorna erro 422
- campanha em massa: item vira `skipped` com motivo no `error_message`

## Cancelamento de campanha

- Endpoint: `PATCH agencia/whatsapp-api-cloud/campanhas/{campaign}/cancelar`
- Efeito:
  - campanha recebe `status = canceled`
  - itens `pending/queued` mudam para `canceled`

## Observações operacionais

- Datas de agendamento são persistidas em UTC.
- Processamento ocorre na fila `processarconversa`.
- Para produção, manter worker ativo para essa fila.
- Limite atual por campanha: `10.000` leads.

## Pontos de evolução

- segmentação avançada além de tags (campos personalizados, recência, origem)
- pausa/retomada de campanha
- relatório detalhado por erro Meta
- reprocessamento de itens com falha
- envio por lotes com limites por conta/qualidade
