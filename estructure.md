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

# Processo Webhook - UazapiWebhookController - UazapiJob

## Verficações iniciais
- Consultar se existe uma conexão registrada atravez do toke do payload.
- Consultar se mensagem veio de grupo.
- Consultar se mensagem veio do administrador.
  - Se mensagem contem # - bot_enable = true.
  - Se mensagem não contém # -  bot_enable = false.

## Consultar registro de conversas
- Consultar se já existe o ClienteLead - Registrar caso não exista.
- Consultar se já existe o AssistantLead - Registrar caso não exista.
  - Chamar createConversation de OpenAIService que retorna o conv_id para a criação do registro.

## Descritografica de midias
- Usar MediaDecryptService para descritografar midias para base64.

## Transcrição de áudio
- Usar metodo transcreverAudio da OpenAIService para receber transcrição de áudio.

## Enviar para OpenAI
- Enviar texto + midias para a OpenAI

## Enviar mensagem para UazapiService
- 