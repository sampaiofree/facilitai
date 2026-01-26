# TABELAS E RELACIONAMENTOS

## Tabela user (Agencias)
## Tabela cliente (relacionamento com user)

====

## tabela Conexao
  'id',
  'name',
  'informacoes',  
  'status',
  'phone',  
  'proxy_ip',
  'proxy_port',
  'proxy_username',
  'proxy_password',  
  'cliente_id' - Relação com a tabela cliente
  'whatsapp_api_id' - Relação com a tabela whatsapp_api 
  'whatsapp_api_key' - null
  'credential_id', (Relação com a tabela credentials)
  'assistant_id', (Relação com a tabela assistant)
  'model', (Relação com a tabela iamodelos)

### Service WebshareService para preencher os campos abaixo
  'proxy_ip',
  'proxy_port',
  'proxy_username',
  'proxy_password',
  'proxy_provider',

===

## Tabela cliente_lead (cliente_id + phone uniq)
  'cliente_id' - Relação com a tabela cliente
  'bot_enabled' - Padrão true
  'phone'
  'name'
  'info' - text

===

## Tabela AssistantLead (lead_id + assistant_id uniq)
  - lead_id - relação com lead
  - assistant_id - relação com assistant
  - version
  - conv_id

===

## Tabela agency_settings
- id
- user_id (unique)
- subdomain (unique)
- custom_domain (nullable, unique)
- domain_verified_at
- app_name
- logo_path
- favicon_path
- support_email
- support_whatsapp
- primary_color
- secondary_color
- timezone
- locale
- created_at
- updated_at


===

## Tabelas do adm
- whatsapp_api (Evolution, UZAPI etc).
- iaplataformas (OPenAI, Gemini etc).
- iamodelos (4.1 mini, 5.1 etc).

===

# Processo Webhook - UazapiWebhookController -> UazapiJob -> ProcessIncomingMessageJob

## Visão geral (fluxo completo)

```
HTTP /api/uazapi/{evento}/{tipodemensagem}
        |
        v
UazapiWebhookController::handle
        |
        v
UazapiJob (padroniza payload + resolve dominio basico)
        |
        v
ProcessIncomingMessageJob (debounce + versionamento + IAOrchestrator + resposta)
        |
        v
UazapiService::sendText (resposta ao cliente)
```

## UazapiWebhookController (entrada HTTP)
- Arquivo: `app/Http/Controllers/Api/UazapiWebhookController.php`
- Rota: `routes/api.php` → `POST /uazapi/{evento}/{tipodemensagem}`
- Responsabilidade:
  - valida `evento === messages`
  - empacota payload bruto em `UazapiJob`

## UazapiJob (padronização e domínio mínimo)
- Arquivo: `app/Jobs/UazapiJob.php`
- Responsabilidade **apenas**:
  - ler o payload bruto recebido da Uazapi
  - resolver `Conexao` via `token`
  - normalizar telefone (whatsapp id → número)
  - deduplicar evento (provider-specific; TTL 10 min)
  - ignorar mensagens de grupo
- montar payload padronizado e despachar `ProcessIncomingMessageJob`
- **despacha apenas IDs** (`conexao_id`, `cliente_lead_id`) + payload normalizado (não envia Models)

### Deduplicação (UazapiJob)
- chave: `dedup:uazapi:{conexao_id}:{eventId}`
- sem `eventId`: usa hash fallback (`conexao_id + phone + message_type + timestamp + text`)
- TTL: **10 minutos**

### Payload padronizado enviado ao ProcessIncomingMessageJob
Campos principais:
- `phone`
- `text` (texto final; tenta `message.text`, depois `content.caption/text`)
- `tipo` (normalizado: `text`, `audio`, `image`, `video`, `document`)
- `from_me`
- `is_group`
- `event_id`
- `message_timestamp`
- `message_type`
- `lead_name`
- `received_at`
- `media` (objeto com campos de mídia)

Campos de mídia (somente dentro de `media`):
- `type` (obrigatório)
- `mimetype` (obrigatório)
- `filename` (obrigatório)
- `base64` (puro, sem prefixo `data:`, quando dentro do limite)
- `storage_key` (quando acima do limite ou vídeo)
- `size_bytes` (obrigatório; tamanho real após `base64_decode`)
- `raw` (opcional, metadados do provider para debug; **somente** com flag)

Limites de payload base64:
- imagem/documento: 300KB (medido em bytes reais)
- áudio: 500KB (medido em bytes reais)
- vídeo: **não envia base64**, apenas `storage_key`

Configuração relacionada:
- `config/media.php` → `media.disk` (default `local`)
- `config/media.php` → `media.raw_enabled` (ou `FEATURE_MEDIA_RAW=true`)
- `IDEMPOTENCY_TTL_HOURS` (default 6h)

## ProcessIncomingMessageJob (processamento completo)
- Arquivo: `app/Jobs/ProcessIncomingMessageJob.php`
- Responsabilidades:
- re-hidrata modelos via IDs (`Conexao::find`, `ClienteLead::find`)
- debounce (re-dispatch)
- idempotência de resposta (anti-duplicação)
- criação/atualização do `ClienteLead`
- versionamento do `AssistantLead` + `createItems` quando versão muda (delegado ao `OpenAIOrchestratorService`)
- transcrição de áudio
- chamada IA via `IAOrchestratorService`
- envio da resposta final via Uazapi

### 1) Regras iniciais
- ignora se `phone` vazio ou `is_group === true`
- carrega `assistant` via `conexao->assistant`
- cria/atualiza `ClienteLead` usando `cliente_id + phone`
- se `from_me === true`:
  - se texto contém `#`, ativa bot (`bot_enabled = true`)
  - senão, desativa bot (`bot_enabled = false`)
  - encerra o fluxo
- se `bot_enabled === false`, encerra o fluxo
- se `tipo` é `video`: envia `sendText` pedindo descrição do vídeo e encerra

### 2) Versionamento / Conversa OpenAI
- executado dentro do `OpenAIOrchestratorService`
- monta `systemPrompt` com os prompts do `Assistant`
- cria `OpenAIService` usando `conexao->credential->token`
- resolve `AssistantLead` (lead + assistant):
  - se não existir: cria conversa OpenAI, salva `conv_id`
  - se existir e versão mudou: chama `createItems` com “Novo contexto...”
  - atualiza `assistantLead->version`

### 3) Debounce (somente texto)
- se mídia → processa imediatamente
- se texto e cache disponível:
  - acumula mensagens no cache
  - re-dispatch do job com `cacheKey`
  - quando tempo expira → junta mensagens e processa
- chave: `debounce:{lead_id}:{assistant_id}`

### 4) Idempotência de resposta
- chave: `resp:{assistant_lead_id}:{hash}`
- hash considera: `assistant_lead_id`, texto agregado (debounce), assinatura de mídia
- TTL: `IDEMPOTENCY_TTL_HOURS` (default 6h)
- verificação **antes** de chamar OpenAI
- marcação **após** envio WhatsApp OK

### 5) Mídia (decrypt)
Processo acontece **no UazapiJob** (provider job):
- usa `MediaDecryptService` → `DescriptoService`
- aplica whitelist de documentos
- converte para base64 **quando dentro do limite**
- acima do limite salva em `Storage::disk(config('media.disk', 'local'))` e passa `storage_key`
- sempre inclui `size_bytes` (bytes reais)
- `raw` só é enviado quando `app.debug=true` ou `FEATURE_MEDIA_RAW=true`
- `ProcessIncomingMessageJob` apenas consome `base64` ou `storage_key`
- falha na descriptografia:
  - log e segue sem mídia (sem retry)

### 6) Transcrição de áudio
usa `OpenAIService::transcreverAudio` com `media.base64` ou `storage_key` (carregado do storage)
- retorna texto transcrito

### 7) Chamada IA (IAOrchestratorService)
- `IAOrchestratorService` resolve provider via `conexao->credential->iaplataforma->nome`
- se `openai`: monta input, executa tools e retorna texto final
- monta `input` com base no tipo:
  - text → `role=user`, `content=text`
  - image → `input_text + input_image (data:...)`
  - document (whitelist) → `input_text + input_file (data:...)`
  - audio → transcrição
  - video → **não chama OpenAI** (responde com pedido de descrição)
- injeta contexto de sistema (data/hora + nome)
- tools via `ToolsFactory::fromSystemPrompt`

### 8) Tools / Function Calls
- usa `OpenAIOrchestratorService` (loop + execução de tools)
- handlers definidos no próprio `ProcessIncomingMessageJob`:
  - `enviar_media` → `UazapiService::sendMedia`
  - `notificar_adm` → `UazapiService::sendText` para admins
  - `buscar_get` → HTTP GET simples
  - `registrar_info_chat` → atualiza `ClienteLead`
  - `enviar_post` → webhook externo
  - `gerenciar_agenda`, `aplicar_tags`, `inscrever_sequencia` → não suportadas

### 9) Envio da resposta final
- extrai texto do assistant da resposta OpenAI
- envia via `UazapiService::sendText`

### 10) Regras de erro OpenAI
- **Transiente** (429/5xx/timeout): lança exception → job falha (sem retry)
- **Permanente** (400/401/403): log estruturado em `SystemErrorLog` e encerra

### Regras de erro de mídia (provider job)
- falha de decrypt → log e segue sem mídia

---

# Arquivos e relacionamentos (mapa completo)

## Entradas HTTP
- `routes/api.php`
  - `POST /uazapi/{evento}/{tipodemensagem}` → `UazapiWebhookController::handle`

## Controllers
- `app/Http/Controllers/Api/UazapiWebhookController.php`
  - recebe payload bruto, despacha `UazapiJob`

## Jobs
- `app/Jobs/UazapiJob.php`
  - normaliza payload, resolve domínio mínimo, dedup, despacha `ProcessIncomingMessageJob`
- `app/Jobs/ProcessIncomingMessageJob.php`
  - debounce, versionamento, IAOrchestrator e resposta

## Services (IA e mídia)
- `app/Services/IAOrchestratorService.php`
  - resolve provider e retorna `IAResult`
- `app/DTOs/IAResult.php`
  - resultado padronizado da IA (sucesso/erro)
- `app/Services/OpenAIOrchestratorService.php`
  - orquestra OpenAI + tools + loop
- `app/Services/OpenAIService.php`
  - chamadas HTTP puras para a OpenAI (responses, conversations, items, transcriptions)
- `app/Services/ToolsFactory.php`
  - cria schemas de tools a partir do system prompt
- `app/Services/MediaDecryptService.php`
  - descriptografa mídia usando `DescriptoService`
- `app/Services/DescriptoService.php`
  - baixa mídia criptografada e decripta (WhatsApp)
- `app/Services/UazapiService.php`
  - envia mensagens e mídias para o WhatsApp

## Models usados no fluxo
- `app/Models/Conexao.php`
  - vínculo com `credential`, `assistant`, `cliente`
- `app/Models/ClienteLead.php`
  - representa o lead (cliente_id + phone)
- `app/Models/AssistantLead.php`
  - vínculo lead + assistant, guarda `version` e `conv_id`
- `app/Models/Assistant.php`
  - contém prompts e versão
- `app/Models/SystemErrorLog.php`
  - armazenamento de erros permanentes OpenAI

## Relações entre arquivos (chamadas diretas)

```
routes/api.php
  -> UazapiWebhookController::handle
       -> UazapiJob
           -> MediaDecryptService -> DescriptoService
           -> ProcessIncomingMessageJob
               -> IAOrchestratorService
                   -> OpenAIOrchestratorService
                       -> OpenAIService (API)
                       -> ToolsFactory
                       -> handlers no ProcessIncomingMessageJob
               -> UazapiService (sendText / sendMedia)
```

## Relações entre modelos
- `Conexao` → `Assistant` (assistant_id)
- `Conexao` → `Credential` (credential_id) → token OpenAI
- `Conexao` → `Cliente` (cliente_id)
- `ClienteLead` → `Cliente` (cliente_id)
- `AssistantLead` → `Assistant` (assistant_id)
- `AssistantLead` → `ClienteLead` (lead_id)

---

# Checklist de logs/erros por etapa

## Entrada HTTP (UazapiWebhookController)
- [ ] evento diferente de `messages` → responder `ignored`
- [ ] payload inválido → responder `ignored`

## UazapiJob (padronização)
- [ ] token ausente → encerrar (sem dispatch)
- [ ] conexao não encontrada → encerrar
- [ ] usuário inválido (`cliente->user_id` vazio) → encerrar
- [ ] telefone inválido → encerrar
- [ ] dedup (chave já existe) → encerrar (silencioso)
- [ ] mensagem de grupo → encerrar
- [ ] payload normalizado sem `phone` → encerrar

## ProcessIncomingMessageJob (início)
- [ ] `phone` vazio ou `is_group` true → encerrar
- [ ] `assistant` ausente → log warning + encerrar
- [ ] falha ao criar/atualizar `ClienteLead` → log warning + encerrar
- [ ] mensagem do admin (`from_me`) → atualizar `bot_enabled` e encerrar
- [ ] `bot_enabled` false → encerrar

## Deduplicação (UazapiJob)
- [ ] chave existe → encerrar (sem log; TTL 10 min)
- [ ] sem `eventId` → usar hash fallback (`conexao_id + phone + message_type + timestamp + text`)

## Versionamento / Conversa OpenAI
- [ ] token OpenAI ausente → log error + encerrar
- [ ] createConversation falhou (exception) → lançar exception (sem retry)
- [ ] createConversation falhou (4xx permanente) → log estruturado + encerrar
- [ ] createConversation sem `id` → log error + encerrar
- [ ] createItems falhou (exception) → lançar exception (sem retry)
- [ ] createItems falhou (4xx permanente) → log estruturado + encerrar

## Debounce
- [ ] cache indisponível → processa texto imediatamente
- [ ] em debounce → re-dispatch do job

## Mídia / Decrypt (UazapiJob)
- [ ] mídia ausente em payload de mídia → log warning + encerrar input
- [ ] decrypt falhou → log warning + encerrar input
- [ ] documento fora da whitelist → log warning + encerrar input

## Transcrição de áudio (OpenAI)
- [ ] transcrição exception/timeout → lançar exception (sem retry)
- [ ] transcrição falhou (4xx permanente) → log estruturado + encerrar
- [ ] transcrição vazia → encerrar input

## OpenAI Responses
- [ ] createResponse exception/timeout → lançar exception (sem retry)
- [ ] createResponse falhou (4xx permanente) → log estruturado + encerrar
- [ ] resposta sem `output` → log warning + encerrar

## Function Calls
- [ ] handler não encontrado → retorna “Função não suportada”
- [ ] handler exception → log error + retorna output padrão
- [ ] resposta após function_call falhou (4xx permanente) → log estruturado + encerrar
- [ ] resposta após function_call exception → lançar exception (sem retry)

## Envio Uazapi
- [ ] token/telefone ausente → log warning + encerrar
- [ ] sendText erro → log error com resposta da Uazapi

## Log estruturado (erros permanentes)
- [ ] status 400/401/403 → gravar `SystemErrorLog`
  - context: `ProcessIncomingMessageJob`
  - function_name: etapa (`createConversation`, `createItems`, `createResponse`, `transcreverAudio`, `function_call_response`)
  - payload: status, body, ids (conexao_id/assistant_id/lead_id/phone)

---

# Logs e Observabilidade (simples para iniciar)

## Canais por domínio (logs diários em arquivo)
- `uazapi_webhook` → entrada HTTP
- `uazapi_job` → normalização/dedup/mídia do provider
- `process_job` → orquestração do fluxo e envio
- `ia_orchestrator` → OpenAI + tools
- `openai` → HTTP client OpenAI
- `media` → decrypt/arquivos de mídia

## Contexto mínimo em toda exceção/log relevante
- `conexao_id`
- `assistant_id`
- `assistant_lead_id`
- `lead_id`
- `phone`
- `event_id`
- `message_type`
- `conversation_id`
- `provider`
- `model`
- `job` / `job_id` / `queue` / `attempt`

## Jobs (falha padrão)
- `failed()` em `UazapiJob` e `ProcessIncomingMessageJob` para logar contexto extra
- tabela `failed_jobs` habilitada por padrão (Laravel)

## Próximo nível (opcional)
- Horizon para monitorar filas Redis
- Sentry/Flare para rastreio de stacktrace e alertas
