<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

use App\Models\Instance;
use App\Models\Chat;
use App\Models\Assistant;
use App\Models\TokensOpenAI;
use App\Models\User;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

use Illuminate\Support\Str;

use App\Services\EvolutionService;
use App\Services\AgendaService;

use Illuminate\Support\Facades\Log;

class ConversationsService
{
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected string $apiKey;

    // Propriedades para guardar os objetos e dados essenciais
    protected ?string $msg;
    protected ?string $numero;
    protected ?int $instanceId;
    protected ?Instance $instance;
    protected ?Chat $chat;
    protected ?Assistant $assistant;
    protected ?EvolutionService $evolutionService;
    protected ?string $conversationId;
    protected ?string $systemPrompt;
    public bool $ready = true;
    public $credential = null;
   

    public function __construct(?string $msg = null, ?string $numero = null, ?int $instanceId = null)
    {
        //$this->apiKey = config('services.openai.key');
        $this->msg = $msg; 
        $this->numero = $numero;
        $this->instanceId = $instanceId;
        $this->evolutionService = new EvolutionService();

        if ($instanceId) {
            $this->instance = Instance::find($instanceId);
            if ($this->instance) { // Adicionado: Verifica se a instância foi encontrada
                
                $this->chat = Chat::where('contact', $numero)->where('instance_id', $instanceId)->first();
                if($this->chat){$this->chat->touch();}
                $this->credential = $this->instance->credential?->id ?? null;

                if ($this->instance->credential?->token) {
                    $this->apiKey = $this->instance->credential?->token;
                } else {
                    //CHAMAR MÉTODO PARA VERIFICAR TOKENS DO USER
                    if (!$this->verificarTokens()) {
                        Log::warning("Usuário: {$this->instance->user_id} não possui tokens");
                        $this->ready = false;
                        $this->enviar_mensagemEVO("Seus tokens acabaram! Para não interromper seus atendimentos, acesse o Dashboard e compre mais agora mesmo.", $this->instance->user->mobile_phone);
                    }else{
                        $this->apiKey = config('services.openai.key');
                    }
                    
                }

                // Busca o assistente com base no ID salvo na instância
                if ($this->instance && $this->instance->default_assistant_id) {
                    $this->assistant = Assistant::where('openai_assistant_id', $this->instance->default_assistant_id)
                    ->orWhere('id', $this->instance->default_assistant_id)
                    ->first();
                }

                if($this->assistant){
                    $this->systemPrompt = 
                        $this->assistant->systemPrompt ."\n".
                        $this->assistant->instructions ."\n".
                        $this->assistant->prompt_notificar_adm ."\n".
                        $this->assistant->prompt_buscar_get ."\n".
                        $this->assistant->prompt_enviar_media ."\n".
                        $this->assistant->prompt_registrar_info_chat ."\n".
                        $this->assistant->prompt_gerenciar_agenda;

                }

                // COMPARAR VERSIONS E ATUALIZAR HISTÓRICO
                if ($this->chat && $this->chat->conv_id && $this->chat->version && $this->chat->version !== $this->assistant->version) {
                    //$systemPrompt = $this->assistant->systemPrompt ?? $this->assistant->instructions;
                    if ($this->createItems($this->chat->conv_id, $this->systemPrompt)) {
                        // Atualiza o version do chat para igualar ao do assistant
                        $this->chat->version = $this->assistant->version;
                        $this->chat->save();
                    }
                }

            }else {
                Log::warning("Instância não encontrada com ID: {$instanceId}");
                $this->notificarDEV("ConversationsService49: Instância não encontrada com ID: {$instanceId}");
                $this->ready = false;
            }
        }
    }

    public function teste(){
        return $this->instance;
    }

    public function verificarTokens(): bool
    {
        $user = User::find($this->instance->user_id);

        if (!$user) {return false;}
        
        // Se 0 tokens → false; se >0 → true
        return $user->tokensAvailable() > 0;
    }

    /**
     * Cria uma nova conversa
     */
        /**
     * Cria uma nova conversa usando os prompts do assistente vinculado à instância.
     */
    public function createConversation()
    {
        // 1. Validação: Garante que um assistente foi encontrado no construtor
        if (!$this->assistant OR !$this->apiKey) {
            Log::warning("Conversarion105: Nenhum assistente válido encontrado para a instância ID: {$this->instanceId}");
            return;
        }

        // 2. Pega os prompts diretamente do objeto Assistant
        /*$systemPrompt = 
        $this->assistant->systemPrompt ."\n".
        $this->assistant->instructions ."\n".
        $this->assistant->prompt_notificar_adm ."\n".
        $this->assistant->prompt_buscar_get ."\n".
        $this->assistant->prompt_enviar_media ."\n".
        $this->assistant->prompt_registrar_info_chat ."\n".
        $this->assistant->prompt_gerenciar_agenda;*/

        //$developerPrompt = $this->assistant->developerPrompt ?? "";

        // Monta o payload para a API
        $payload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => $this->systemPrompt,
                ],
                /*[
                    'type' => 'message',
                    'role' => 'developer',
                    'content' => $developerPrompt,
                ],*/
            ],
        ];

        // 3. Faz a chamada para a API
        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/conversations", $payload);

        //falha    
        if ($response->failed()) {
            $this->notificarDEV("ConversationsService 95: ".json_encode($response->body()));
            Log::warning("Erro ao criar conversa na API: " . $response->body());
            return;
        }else{
            $convId = $response->json()['id'] ?? null;
            if (!$convId) {
                $this->notificarDEV("ConversationsService 100: createConversation não retornou ID");
                Log::warning("API não retornou ID da conversa."); return;
            }

            $this->conversationId = (string)$convId;
            $this->chatAtualizar();

            return (string)$convId;
        }
        
    }

    public function createItems(string $conversationId, string $novoPrompt): bool
    {
        $payload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => "Novo contexto atualizado:\n\n{$novoPrompt}"
                        ]
                    ]
                ],
            ],
        ];

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/conversations/{$conversationId}/items", $payload);

        if ($response->failed()) {
            Log::error("Falha ao atualizar contexto da conversa: " . $response->body());
            return false;
        }

        return true;
    }


    public function chatAtualizar(){
        Chat::updateOrCreate(
                [
                    // Condições para encontrar o registro:
                    'contact' => $this->numero,
                    'instance_id' => $this->instanceId,
                ],
                [
                    // Valores para salvar (seja criando ou atualizando):
                    'user_id' => $this->instance->user_id,
                    'assistant_id' => $this->instance->default_assistant_id,
                    'conv_id' => $this->conversationId,
                    'version' => (int)$this->assistant->version ?? 1
                ]
            );
    }

    /**
     * Recebe dados do Evolution API, processa e reponde API Evolution.
     */
    public function enviarMSG(){

        if (!$this->apiKey) {
            Log::warning("apiKey inválida, usuário sem Tokens"); return;
        }

        if($this->msg){
            $input[] = [
                'role' => 'user',
                'content' => $this->msg,
            ];
            $modelo = $this->instance?->model ?? 'gpt-4.1-mini';
            return $this->createResponse($input, $modelo);
        }else{
            return false;
        }
       
    }


    /**
     * Recebe dados do Evolution API, processa e reponde API Evolution.
     */
    public function evolution($data){

        if (!$this->apiKey) {
            Log::warning("apiKey inválida, usuário sem Tokens"); return;
        }

        $messageType = $data['messageType']; //"messageType": "conversation" / "messageType": "audioMessage" / "messageType": "imageMessage", /"messageType": "documentMessage",
        $messageData = $data['message'];


        $input = [];
        if($messageType == 'conversation'){
            $input[] = [
                'role' => 'user',
                'content' => $messageData['conversation'] ?? '',
            ];
        }elseif($messageType == 'audioMessage'){
            $input[] = [
                'role' => 'user',
                'content' => $this->transcreverAudio($messageData['base64']),
            ];

            //Log::info("Request: " , $input); 
        }elseif($messageType == 'imageMessage'){
            $base64 = $messageData['base64'] ?? null;
            $input[] = [
                "role" => "user",
                "content" => [
                    [
                        "type" => "input_text",
                        "text" => $messageData['imageMessage']['caption'] ?? 'Estou enviando esta imagem.'
                    ],
                    [
                        "type" => "input_image",
                        "image_url" => "data:image/jpeg;base64,{$base64}"
                    ]
                ]
            ];
        }elseif($messageType == 'documentMessage'){
            $base64 = $messageData['base64'] ?? null;
            $input[] = [
                "role" => "user",
                "content" => [
                    [
                        "type" => "input_text",
                        "text" => $messageData['documentMessage']['caption'] ?? 'Estou enviando este documento.'
                    ],
                    [
                        "type" => "input_file",
                        "filename" => $messageData['documentMessage']['fileName'],
                        "file_data" => "data:".$messageData['documentMessage']['mimetype'].";base64,{$base64}"
                    ]
                ]
            ];

        }

        //Log::info("Request: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); 
        $modelo = $this->instance?->model ?? 'gpt-4.1-mini';
        Log::info("modelo: " . $modelo);
        $this->createResponse($input, $modelo);
    }
    
   
    /**
     * Cria uma nova resposta (mensagem) dentro de uma conversa existente ou nova.
     * Os dados da conversa e da mensagem vêm do construtor do serviço.
     *
     * @param string $model Opcional: O modelo de IA a ser usado.
     * @param array $tools Opcional: Ferramentas customizadas a serem adicionadas.
     * @return array A resposta da API.
     */
    public function createResponse($input, string $model = 'gpt-4.1-mini', $dd = false)
    {
        if (!$this->apiKey) {
            
            Log::warning("apiKey inválida, usuário sem Tokens"); return;
        }

        // Determina o ID da conversa
        // Pega do objeto Chat (se ele já existir e tiver um conv_id)
        $this->conversationId = $this->chat->conv_id ?? $this->createConversation();

        //AGORA O ASSISTENTE SABER QUE DIA É HOJE
        $hoje = now(config('app.timezone', 'America/Sao_Paulo'))->toDateString();
        // antes de montar $payload:
        $input = array_merge([
        [
            'role' => 'system',
            'content' => "Hoje é {$hoje} (timezone: ".config('app.timezone','UTC').")."
        ]
        ], $input);

        // 2. Define as ferramentas customizadas com base nos prompts do assistente
        if (str_contains($this->systemPrompt, 'notificar_adm')) {
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
                                'description' => 'Lista de números de telefone dos administradores.'
                            ],
                            'mensagem' => [
                                'type' => 'string',
                                'description' => 'A mensagem a ser enviada para os administradores.'
                            ],
                        ],
                        'required' => ['numeros_telefone', 'mensagem'],
                        'additionalProperties' => false,
                    ],
                    'strict' => true,
                ];
        }    
        if (str_contains($this->systemPrompt, 'enviar_media')) {
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
                                'description' => 'A URL da imagem ou vídeo que será enviada. Verifique se a URL é de uma imagem ou vídeo acessível publicamente.'
                            ],
                        ],
                        'required' => ['url'],
                        'additionalProperties' => false,
                    ],
                    'strict' => true,
                ];
        }    
        if (str_contains($this->systemPrompt, 'buscar_get')) {
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
                                'description' => 'A URL completa da fonte da informação.'
                            ],
                        ],
                        'required' => ['url'],
                        'additionalProperties' => false,
                    ],
                    'strict' => true,
                ];
        }    
        if (str_contains($this->systemPrompt, 'registrar_info_chat')) {
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
                                'description' => 'Nome do cliente ou contato identificado.'
                            ],
                            'informacoes' => [
                                'type' => 'string',
                                'description' => 'Informações adicionais sobre o atendimento.'
                            ],
                        ],
                        'required' => ['nome','informacoes'],
                        'additionalProperties' => false,
                    ],
                    'strict' => true,
                ];
        }
    
        if(isset($this->instance->agenda_id)){
            $tools[] =
            [
            'type' => 'function',
            'name' => 'gerenciar_agenda',
            'description' => <<<TXT
                Use esta ferramenta para **consultar, agendar, cancelar ou alterar horários** na agenda do sistema interno.

                **Objetivo:** conectar com o sistema de agendamentos e gerenciar horários disponíveis.

                * Sempre use esta ferramenta quando o usuário falar sobre **horários, agendamentos, disponibilidade ou reagendamentos**.  
                * **Nunca mencione ou solicite IDs dos horários** ao usuário. Os IDs são apenas para uso interno no uso da ferramenta.  
                * Mostre os horários de forma natural, por exemplo:  
                “Segunda, 06 de outubro — às 12h.”  
                * Se houver muitos horários, pergunte primeiro o **melhor dia** e depois o **período** (manhã, tarde ou noite).  
                * Se o usuário disser apenas “tem horário livre?”, consulte o **mês atual** automaticamente.  

                ---

                ### 🔹 Regras principais

                - ✅ **Consultar:** use `"acao": "consultar"` para mostrar horários disponíveis.  
                Mostre apenas alguns horários e **convide o usuário a escolher dizendo o dia e horário desejado** (exemplo: “Qual desses horários você prefere?”).  

                - ✅ **Agendar:** use `"acao": "agendar"` para reservar um horário.  
                Use o horário que o usuário escolheu (sem mencionar IDs).  
                Peça nome e telefone se ainda não tiver.  

                - ✅ **Cancelar:** use `"acao": "cancelar"` para desmarcar um horário.  
                Confirme com o usuário antes.  

                - ✅ **Alterar:** use `"acao": "alterar"` para trocar um horário.  
                Confirme o novo horário escolhido antes de executar.  

                ---

                ### ⚠️ Observações

                - O campo **mes** representa o mês (1–12).  
                - Se o usuário não informar o mês, use o **mês atual**.  
                - O **ano** é sempre o atual.  
                - Use **expressões naturais**, nunca códigos técnicos como IDs.  
                - Se houver dúvida sobre o horário exato, peça confirmação (“Prefere às 12h ou 12h30?”).  

                **Ações suportadas:** consultar, agendar, cancelar, alterar
                TXT,


            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'acao' => [
                        'type' => 'string',
                        'enum' => ['consultar', 'agendar', 'cancelar', 'alterar'],
                        'description' => 'Tipo de operação desejada na agenda.'
                    ],
                    'mes' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => 12,
                        'description' => "Número do mês (1 a 12). Se não informado, usar mês atual ".date("m")."."
                    ],
                    'telefone' => [
                        'type' => 'string',
                        'description' => 'Telefone do cliente (usado apenas ao agendar).'
                    ],
                    'nome' => [
                        'type' => 'string',
                        'description' => 'Nome do cliente (usado apenas ao agendar).'
                    ],
                    'disponibilidade_id' => [
                        'type' => 'integer',
                        'description' => 'ID da disponibilidade (horário específico) usado para agendar ou cancelar.'
                    ],
                    'nova_disponibilidade_id' => [
                        'type' => 'integer',
                        'description' => 'ID da nova disponibilidade (usado em "alterar" para reagendar).'
                    ],
                ],
                'required' => ['acao', 'mes'],
                'additionalProperties' => false,
            ],
            'strict' => false,
        ];

        }    

        // 3. Monta o payload para a API
        $payload = [
            'model' => $model,
            //'strict' => true,
            //'temperature' => 0.8,
            //"max_output_tokens" => 400,
            'input' => $input,
            // Define a ferramenta padrão de busca na web
            'tools' => $tools,
            // Adiciona o ID da conversa ao payload
            'conversation' => $this->conversationId,
            
        ];


        // 4. Faz a chamada para a API
        /*$response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/responses", $payload);
        

        if ($response->failed()) {
            Log::info('Erro API Responser 299', [$response->json()]); 
            sleep(40); // Espera 40 segundos antes de tentar novamente
            $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/responses", $payload);

            // Se ainda falhar, registra o erro e notifica o desenvolvedor
            if ($response->failed()) {
                Log::error('Erro ao criar response na API', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'url'    => $response->effectiveUri() ?? null, // se quiser registrar a URL chamada
                ]);

                $this->notificarDEV("ConsersationService 255: ".json_encode($response->body()));
                Log::warning("Erro ao criar response na API: " . $response->body()); return;
            }
        }

        //4
        $maxTentativas = 3;
        $tentativa = 0;

        do {
            $tentativa++;
            $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/responses", $payload);

            if ($response->successful()) {
                break; // Sai do loop se deu certo
            }

            $erro = $response->json()['error']['code'] ?? null;

            // Se for erro de conversa bloqueada, aguarda e tenta novamente
            if ($erro === 'conversation_locked' OR $erro ==='rate_limit_exceeded') {
                Log::warning("🔄 Tentativa {$tentativa}/{$maxTentativas} - Conversa bloqueada. Aguardando 20s...");
                sleep(20);
            } else {
                // Outros erros devem sair imediatamente
                break;
            }
        } while ($tentativa < $maxTentativas);

        // Após tentar todas as vezes
        if ($response->failed()) {
            Log::error('❌ Erro ao criar response na API após múltiplas tentativas', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'tentativas' => $tentativa,
            ]);

            $this->notificarDEV("ConversationsService: Erro após {$tentativa} tentativas. Erro: {$response->body()}");
            return;
        }*/

        $response = $this->postResponse($payload);

        if (!$response) {
            return false;
        }

        // 5. Retorna a resposta completa da API
        $apiResponse = $response->json();

        //dd($apiResponse);

        $lastOutput = end($apiResponse['output']);

        //REGISTRAR TOKENS
        if(isset($apiResponse['usage']['total_tokens'])){
            $this->registrarTokens($apiResponse['usage']['total_tokens'], $apiResponse['id']);
        }

        // Procura função e última mensagem do assistente antes dela
        $functionCallFound = false;
        $assistantMsgBeforeCall = null;

        foreach ($apiResponse['output'] as $item) {
            if (($item['type'] ?? null) === 'message' && ($item['role'] ?? null) === 'assistant') {
                $assistantMsgBeforeCall = $item['content'][0]['text'] ?? null;
            }
            if (($item['type'] ?? null) === 'function_call') {
                $functionCallFound = true;
                break; // para manter a “ultima mensagem antes da função”
            }
        }

        if ($functionCallFound) {
            if ($assistantMsgBeforeCall) {
                $this->enviar_mensagemEVO($assistantMsgBeforeCall); // envia o “Vou te enviar um áudio...”
            }

            $mensagem = $this->submitFunctionCall($apiResponse);
            if (!$mensagem) { return false; }

            $this->enviar_mensagemEVO($mensagem);
            return true;
        }
        
        //CHAMADA DE FUNÇÃO
        /*if ($lastOutput && isset($lastOutput['type']) && $lastOutput['type'] === 'function_call') {
            // submitFunctionCall agora deve retornar a resposta final da API
            $mensagem = $this->submitFunctionCall($apiResponse);
            if(!$mensagem){return false;}
            $this->enviar_mensagemEVO($mensagem);
            return true;
        }*/

        //RESPOSTA DO ASSISTENTE
        $mensagem = null; // Inicializa a mensagem como nula
        // Tenta obter a mensagem do assistente do último item diretamente
        if (
            ($lastOutput['type'] === 'message' OR $lastOutput['type'] === 'output_text') &&
            isset($lastOutput['role']) && $lastOutput['role'] === 'assistant' &&
            isset($lastOutput['content'][0]['text'])
        ) {
            $mensagem = $lastOutput['content'][0]['text'];
        } else {
            // Fallback: Se o último item não for uma mensagem do assistente, procura na ordem inversa
            foreach (array_reverse($apiResponse['output']) as $outputItem) {
                if (
                    isset($outputItem['type']) && ($lastOutput['type'] === 'message' OR $lastOutput['type'] === 'output_text') &&
                    isset($outputItem['role']) && $outputItem['role'] === 'assistant' &&
                    isset($outputItem['content'][0]['text'])
                ) {
                    $mensagem = $outputItem['content'][0]['text'];
                    break; // Encontrou a primeira mensagem do assistente de trás para frente, sai do loop
                }
            }
        }

        if ($mensagem !== null) {
            $this->enviar_mensagemEVO($mensagem);
            return true;
        } 
        
        $this->notificarDEV("ConversationsService 2645: Nenhuma mensagem do assistente encontrada no output da API para enviar. Conversation ID: {$this->conversationId}");
        Log::warning("Nenhuma mensagem do assistente encontrada no output da API para enviar.");
        return true;
        
    }


    public function submitFunctionCall(array $apiResponse)
    {
        if (!$this->apiKey) {
            Log::warning("apiKey inválida, usuário sem Tokens"); return;
        }

        $tool_outputs = [];
        foreach($apiResponse['output'] as $output) {
            if ($output['type'] === 'function_call') {
                $tool_outputs[] = $this->handleFunctionCall($output);
            }
        }

        $modelo = $this->instance?->model ?? 'gpt-4.1-mini';
        $payload = [
            'model' => $modelo,
            //'temperature' => 0.8,
            //"max_output_tokens" => 400,
            'input' => $tool_outputs,
            'conversation' => $this->conversationId,
        ];

        //dd($payload);

        /*$response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/responses", $payload);

        if ($response->failed()) {
            $this->notificarDEV("ConsersationService 346: ".json_encode($response->body()));
            
            Log::warning("Erro ao processar chamada de função na API: " . $response->body()); return;
        }*/

        $response = $this->postResponse($payload);

        if (!$response) {
            return false;
        }    

        $apiResponse = $response->json();
        $lastOutput = end($apiResponse['output']);

        //REGISTRAR TOKENS
        if(isset($apiResponse['usage']['total_tokens'])){
            $this->registrarTokens($apiResponse['usage']['total_tokens'], $apiResponse['id']);
        }

        // Tenta obter a mensagem do assistente do último item diretamente
        if (
            ($lastOutput['type'] === 'message' OR $lastOutput['type'] === 'output_text') &&
            isset($lastOutput['role']) && $lastOutput['role'] === 'assistant' &&
            isset($lastOutput['content'][0]['text'])
        ) {
            return $lastOutput['content'][0]['text'];
        } else {
            // Fallback: Se o último item não for uma mensagem do assistente, procura na ordem inversa
            foreach (array_reverse($apiResponse['output']) as $outputItem) {
                if (
                    isset($outputItem['type']) && ($lastOutput['type'] === 'message' OR $lastOutput['type'] === 'output_text') &&
                    isset($outputItem['role']) && $outputItem['role'] === 'assistant' &&
                    isset($outputItem['content'][0]['text'])
                ) {
                    return $outputItem['content'][0]['text'];
                }
            }
        }

        $this->notificarDEV("ConversationsService 322: Nenhuma mensagem retornada de submitFunctionCall. Conversation ID: {$this->conversationId}");
        
    }

    public function postResponse(array $payload){
        $maxTentativas = 3;
        $tentativa = 0;

        do {
            $tentativa++;
            $response = Http::withToken($this->apiKey)->post("{$this->baseUrl}/responses", $payload);

            if ($response->successful()) {
                return $response;
            }

            $erro = $response->json()['error']['code'] ?? null;

            // Se for erro de conversa bloqueada, aguarda e tenta novamente
            if ($erro === 'conversation_locked' OR $erro ==='rate_limit_exceeded') {
                return false;
                //Log::warning("🔄 Tentativa {$tentativa}/{$maxTentativas} - Conversa bloqueada. Aguardando 30s...");
                //sleep(30);
            } else {
                return false;
            }
        } while ($tentativa < $maxTentativas);

        // Após tentar todas as vezes
        if ($response->failed()) {
            Log::error('❌ Erro ao criar response na API após múltiplas tentativas', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'tentativas' => $tentativa,
            ]);

            $this->notificarDEV("ConversationsService: Erro após {$tentativa} tentativas. Erro: {$response->body()}");
            return false;
        }
    }


    public function handleFunctionCall(array $functionCall){
        $functionName = $functionCall['name'];
        $arguments = json_decode($functionCall['arguments'], true);
        //Log::info('arguments: ', [$arguments]);
        if ($functionName === 'enviar_media') {
            $this->enviar_media($arguments['url']);
            return [
                    "type" => "function_call_output",     
                    'call_id' => $functionCall['call_id'],
                    'output' => 'Mídia enviada para a fila de envio.'
            ];
        }

        if ($functionName === 'notificar_adm') {
            $this->notificar_adm($functionCall['arguments']);
            return [
                    "type" => "function_call_output",      
                    'call_id' => $functionCall['call_id'],
                    'output' => 'Notificação enviada para o administrador.'
            ];
        }

        if ($functionName === 'buscar_get') {
            //Log::info('arguments: ', [$arguments]);
            $res = $this->buscar_get($arguments['url']);
            
            return [
                    "type" => "function_call_output",      
                    'call_id' => $functionCall['call_id'],
                    'output' => (string)$res
            ];
        }
        if ($functionName === 'registrar_info_chat') {
            $this->registrar_info_chat(
                $arguments['nome'] ?? null,
                $arguments['informacoes'] ?? null,
            );

            return [
                "type" => "function_call_output",
                'call_id' => $functionCall['call_id'],
                'output' => 'Informações do chat registradas com sucesso.'
            ];
        }

        if ($functionName === 'gerenciar_agenda') {
            $resultado = $this->gerenciar_agenda($arguments);
            return [
                "type" => "function_call_output",
                'call_id' => $functionCall['call_id'],
                'output' => $resultado['output'] ?? 'Ação de agenda executada.'
            ];
        }

    }

    public function gerenciar_agenda(array $arguments)
    {
        if(!isset($this->instance->agenda_id)){
            $resultado['output'] = "⚠️ A funcionalidade de agenda não está habilitada para esta instância.";
            return $resultado;
        }

        try {
            $agendaService = new AgendaService();

            // Log opcional para depuração
            //Log::info('📅 [ConversationsService] Chamando gerenciar_agenda', $arguments);

            // Chama o método central do AgendaService
            $resultado = $agendaService->executarAcao(
                $arguments['acao'] ?? '',
                [
                    'agenda_id' => $this->instance->agenda_id ?? null,
                    'chat_id' => $this->chat->id ?? null,
                    'telefone' => $arguments['telefone'] ?? ($this->chat->contact ?? null),
                    'nome' => $arguments['nome'] ?? ($this->chat->nome ?? null),
                    'data_inicio' => $arguments['data_inicio'] ?? null,
                    'data_fim' => $arguments['data_fim'] ?? null,
                    'disponibilidade_id' => $arguments['disponibilidade_id'] ?? null,
                    'nova_disponibilidade_id' => $arguments['nova_disponibilidade_id'] ?? null,
                ]
            );

            // Padroniza a resposta para o fluxo da OpenAI
            if ($resultado['success'] ?? false) {
                $msg = match ($arguments['acao']) {
                    'consultar' => $this->formatarConsulta($resultado['data']),
                    'agendar'   => "✅ Horário agendado com sucesso para *{$resultado['data']['data']}* às *{$resultado['data']['inicio']}*!",
                    'cancelar'  => "🗓️ O horário foi cancelado com sucesso.",
                    'alterar'   => "🔄 O agendamento foi alterado com sucesso!",
                    default     => "✅ Ação executada com sucesso.",
                };
            } else {
                $msg = "⚠️ " . ($resultado['message'] ?? 'Não foi possível concluir a ação.');
            }

            return [
                "type" => "function_call_output",
                "call_id" => $arguments['call_id'] ?? null,
                "output" => $msg
            ];

        } catch (\Throwable $e) {
            Log::error('❌ Erro em gerenciar_agenda: ' . $e->getMessage(), ['args' => $arguments]);
            return [
                "type" => "function_call_output",
                "call_id" => $arguments['call_id'] ?? null,
                "output" => "❌ Erro interno ao tentar gerenciar a agenda. Tente novamente mais tarde."
            ];
        }
    }

    private function formatarConsulta($disponibilidades)
    {
        if (empty($disponibilidades) || count($disponibilidades) === 0) {
            return "📅 Nenhum horário disponível no período informado.";
        }

        $texto = "🗓️ *Horários disponíveis:*\n\n";
        foreach ($disponibilidades as $disp) {
            $data = \Carbon\Carbon::parse($disp['data'])->format('d/m');
            $texto .= "• {$data} - {$disp['inicio']} até {$disp['fim']} (ID: {$disp['id']})\n";
        }

        $texto .= "\nEnvie o *ID* do horário que deseja agendar.";
        return $texto;
    }



    public function registrar_info_chat(?string $nome, ?string $informacoes, bool $aguardando = false)
    {
        try {
            if (!$this->chat) {
                Log::warning("registrar_info_chat: Chat não encontrado para o número {$this->numero}");
                return false;
            }

            $this->chat->update([
                'nome' => $nome,
                'informacoes' => $informacoes,
                'aguardando_atendimento' => true,
            ]);

            /*Log::info("📝 Chat atualizado com sucesso", [
                'chat_id' => $this->chat->id,
                'nome' => $nome,
                'informacoes' => $informacoes,
                'aguardando_atendimento' => $aguardando,
            ]);*/

            return true;
        } catch (\Throwable $e) {
            Log::error("Erro ao registrar informações do chat: " . $e->getMessage());
            return false;
        }
    }


    public function notificarDEV($mensagemErro){ 
        $msg = (string)$mensagemErro.".\n Número".(string)$this->numero.".\n Instância: ".(string)$this->instanceId;
        $this->evolutionService->enviar_msg_evolution('5562995772922', (string)$msg, '177',);
        $this->enviar_mensagemEVO("😔 Opa, parece que tivemos um problema técnico e sua pergunta não chegou certinho. Por favor, envie sua pergunta novamente para que eu possa te ajudar. Agradeço pela compreensão! 🙏");
        return true;
    }

    //NOTIFICAR ADMINISTRADOR DE ATENDIMENTO EM ABERTO
    public function notificar_adm($arguments){
        $this->evolutionService->notificar_adm($arguments, $this->instanceId, $this->numero);
        return true;
    }

    //ENVIAR MIDIA (IMAGEM OU VÍDEO)
    public function enviar_media($url)
    {
        // Verifica se termina com .jpg, .png ou .mp4
        if (Str::endsWith($url, ['.jpeg','.jpg', '.png', '.mp4', '.pdf', '.mp3'])) {
            $this->evolutionService->enviarMedia($this->numero, $url, $this->instanceId);
            return true;
        }

        // Caso não seja uma mídia válida
        return false;
    }

    //ENVIAR MENSAGEM USANDO EVOLUTION
    public function enviar_mensagemEVO($mensagem, $numero = null, $instanceId = null){
        
        if(isset($this->assistant)){
            
            $esperar = $this->assistant->delay ?? 0;
            sleep($esperar);
        }

        $numero = $numero ?? $this->numero;
        $instanceId = $instanceId ?? $this->instanceId;

        $mensagem = str_replace("**", "*", $mensagem); // Substitui negrito do Markdown pelo do WhatsApp
        $this->evolutionService->enviar_msg_evolution($numero, $mensagem, $instanceId);
        return true;
    }

    public function getConversationItems(string $conversationId, int $limit = 50)
    {

        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/conversations/{$conversationId}/items", [
                'limit' => $limit
            ]);

        dd($response->json());    

    }

    public function registrarTokens(int $tokens, $resp_id)
    {
        if (!$this->instanceId) {
            Log::warning("Não foi possível registrar tokens: Instância ou chat não definidos.");
            return false;
        }

        TokensOpenAI::Create([ 
            'conv_id' => $this->conversationId,
            'credential_id' => $this->credential,
            'resp_id' => $resp_id,
            'contact' => $this->numero,
            'instance_id' => $this->instanceId,
            'tokens'       => DB::raw('COALESCE(tokens, 0) + ' . (int)$tokens),
            'user_id' => $this->instance->user_id
        ]);

        return true;
    }

    public function transcreverAudio(string $base64): ?string
    {
        $apiKey = $this->apiKey;
        $tmpPath = storage_path('app/tmp/');
        $originalAudioPath = $tmpPath . 'audio.ogg';
        $convertedAudioPath = $tmpPath . 'audio.mp3';

        try {
            // Garante que o diretório existe
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath, 0777, true);
            }

            // Salva o áudio original recebido do Evolution
            file_put_contents($originalAudioPath, base64_decode($base64));

            // Converte para MP3 com ffmpeg
            $result = Process::run("ffmpeg -i {$originalAudioPath} -acodec libmp3lame -q:a 2 {$convertedAudioPath} -y");

            if (!$result->successful()) {
                Log::error("Erro na conversão FFmpeg", [
                    'exit_code' => $result->exitCode(),
                    'error' => $result->errorOutput(),
                ]);
                return null;
            }

            // Faz a requisição para a OpenAI Transcription API
            $response = Http::withToken($apiKey)
                ->asMultipart()
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'file' => fopen($convertedAudioPath, 'r'),
                    'model' => 'whisper-1', // Pode usar whisper-1 ou gpt-4.1-mini-transcribe
                    'language' => 'pt',
                ]);

            // Log para debug
            /*Log::info("Resposta Transcription", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);*/

            // Se deu certo, retorna apenas o texto
            if ($response->successful()) {
                //Log::info("613", [$response->json()]);
                return $response->json('text');
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Erro na transcrição: " . $e->getMessage());
            return null;
        } finally {
            // Limpa arquivos temporários
            if (file_exists($originalAudioPath)) unlink($originalAudioPath);
            if (file_exists($convertedAudioPath)) unlink($convertedAudioPath);
        }
    }

    public function buscar_get(string $url): string
    {
        try {

            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'follow_location' => 1,
                    'ignore_errors' => true,
                    'header' => "User-Agent: Mozilla/5.0\r\n"
                ],
                'https' => [
                    'timeout' => 10,
                    'follow_location' => 1,
                    'ignore_errors' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'header' => "User-Agent: Mozilla/5.0\r\n"
                ]
            ]);

            $conteudo = @file_get_contents($url, false, $context);

            $maxChars = 30000; //limite de caracteres
            $conteudo = trim($conteudo);
            if (strlen($conteudo) > $maxChars) {
                $conteudo = mb_substr($conteudo, 0, $maxChars, 'UTF-8');
            }

            if ($conteudo === false) {
                //Log::info('conteudo: ', $conteudo);
                Log::error('Erro ao obter conteúdo de: ' . $url);
                return "⚠️ Não foi possível obter conteúdo da URL.";
            }

            return trim($conteudo);

        } catch (\Throwable $e) {
            Log::error('Erro em buscar_get: ' . $e->getMessage());
            return "❌ Erro ao buscar conteúdo da URL.";
        }
    }


}
