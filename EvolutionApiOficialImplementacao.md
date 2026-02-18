# Evolution API Oficial - Status de Implementacao e Proximas Etapas

## 1. Objetivo
Documentar o que ja foi implementado no fluxo da Evolution API Oficial, os pontos pendentes e a abordagem da proxima etapa.

## 2. Decisoes do projeto
- Endpoint de webhook: `POST /api/evolution-api-oficial`
- Evento aceito: apenas `messages.upsert`
- Identificacao da conexao: `payload.instance` deve ser `conexao.id`
- Dedupe principal: `data.key.id`
- Grupos: ignorar
- Seguranca de webhook: mantida como esta por enquanto (fora de escopo atual)

## 3. Status atual (implementado)

### 3.1 Webhook e enfileiramento
- `EvolutionApiOficialWebhookController` recebe o webhook, normaliza evento, remove `apikey` do payload e enfileira `EvolutionApiOficialJob` na fila `webhook`.
- Eventos diferentes de `messages.upsert` sao ignorados com log estruturado.

### 3.2 Normalizacao e dedupe no job
- `EvolutionApiOficialJob`:
  - resolve `Conexao` por `instance`
  - valida `whatsappApi.slug = api_oficial`
  - ignora grupos
  - normaliza telefone
  - normaliza tipo (`text/audio/image/document/video`)
  - gera `event_id` com prefixo `evo:`
  - deduplica com cache TTL
  - despacha `ProcessIncomingMessageJob` na fila `processarconversa`

### 3.3 Contrato normalizado enviado ao processamento
- Campos base:
  - `phone`, `text`, `tipo`, `from_me`, `is_group`
  - `event_id`, `message_timestamp`, `message_type`
  - `lead_name`, `received_at`, `media`
- Campos adicionais:
  - `provider = api_oficial`
  - `provider_event`
  - `provider_instance`
  - `provider_instance_id`

### 3.4 Midia inbound (audio/image/document/video)
- Para `audio/image/document`:
  - extrai metadados de midia
  - baixa binario da URL do payload
  - valida tamanho maximo
  - salva como `base64` ou `storage_key` conforme limite
  - rejeita documento fora de whitelist
- Para `video`:
  - mantem metadados; processamento textual continua via fallback ja existente
- Captions:
  - `image/document/video` sao consideradas em `text` quando presentes

### 3.5 Download de midia com proxy e fallback
- O job tenta baixar com proxy da propria `Conexao` (`proxy_ip/port/username/password`) quando habilitado.
- Se o download via proxy falhar, faz fallback para download direto.
- Validacao opcional de `Content-Type` da resposta.

### 3.6 Roteamento por provedor no processamento
- `ProcessIncomingMessageJob` ja roteia por `conexao->whatsappApi->slug`:
  - `sendText`: `uazapi` ou `api_oficial`
  - `handleEnviarMedia`: `uazapi` ou `api_oficial`
  - `handleNotificarAdm`: usa `sendText` roteado
- `sendPresence`:
  - `uazapi`: ativo
  - `api_oficial`: ativo via `EvolutionAPIOficial::messagePresence`

### 3.7 Melhorias de robustez adicionais
- `handleNotificarAdm`:
  - valida mensagem vazia
  - sanitiza e deduplica numeros antes de enviar
- `EvolutionAPIOficial`:
  - fail-fast de configuracao (nao chama API sem `EVOLUTION_URL` e `EVOLUTION_GLOBAL_API_KEY`)
  - validacao de URL configurada
- Parser booleano robusto para configs de midia:
  - evita comportamento inconsistente quando `.env` vier vazio/`''`

## 4. Configuracoes relevantes

### 4.1 Services
- `EVOLUTION_URL`
- `EVOLUTION_GLOBAL_API_KEY`

### 4.2 Media (Evolution Oficial)
- `EVOLUTION_OFICIAL_MEDIA_DOWNLOAD_TIMEOUT`
- `EVOLUTION_OFICIAL_MEDIA_DOWNLOAD_RETRY_TIMES`
- `EVOLUTION_OFICIAL_MEDIA_DOWNLOAD_RETRY_SLEEP_MS`
- `EVOLUTION_OFICIAL_MEDIA_MAX_DOWNLOAD_BYTES`
- `EVOLUTION_OFICIAL_MEDIA_USE_CONEXAO_PROXY`
- `EVOLUTION_OFICIAL_MEDIA_VALIDATE_RESPONSE_CONTENT_TYPE`

### 4.3 Media geral
- `MEDIA_DISK`
- `FEATURE_MEDIA_RAW`

## 5. Pendencias conhecidas
- Sem testes automatizados cobrindo o fluxo Evolution Oficial.
- Sem smoke test manual ponta a ponta validado em ambiente real.
- Seguranca de webhook (assinatura/header) propositalmente adiada.

## 6. Proxima etapa recomendada (Etapa 5)
Objetivo: garantir confiabilidade por teste automatizado antes do rollout completo.

### 6.1 Abordagem
- Criar testes de feature e unit para o fluxo Evolution Oficial, sem depender de API externa real.
- Usar `Queue::fake()`, `Http::fake()`, `Storage::fake()` e payload fixtures.
- Validar contrato normalizado, dedupe e roteamento de envio por provedor.

### 6.2 Escopo tecnico da etapa
- Testes do controller de webhook:
  - ignora evento fora de `messages.upsert`
  - enfileira job em `webhook` para evento valido
- Testes do `EvolutionApiOficialJob`:
  - normalizacao de `text/audio/image/document/video`
  - dedupe por `data.key.id`
  - rejeicao de grupo
  - fallback de caption
  - fallback proxy -> direto no download
  - bloqueio por documento fora de whitelist
- Testes de roteamento no `ProcessIncomingMessageJob`:
  - `uazapi` envia por `UazapiService`
  - `api_oficial` envia por `EvolutionAPIOficial`
  - `presence` por provedor
  - `handleNotificarAdm` com numeros deduplicados

### 6.3 Criterios de aceite da etapa
- Cobertura dos caminhos criticos do fluxo Evolution Oficial.
- Nenhuma regressao no fluxo Uazapi.
- Execucao de testes verde em CI local (`php artisan test`).

## 7. Fora de escopo desta etapa
- Endurecimento de seguranca do webhook.
- Mudancas de produto/UX no front de conexoes.
- Validacao de status de conexao na API Oficial (ainda indisponivel).
