<?php

namespace App\Services;

use OpenAI\Factory; 
use OpenAI\Contracts\ClientContract;
use App\Services\EvolutionService;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\LeadEmpresa;
use Illuminate\Support\Facades\Process;

class OpenAIService
{
    protected ClientContract $client;
    protected string $apiKey;
    protected $evolution;
    public $instancia;
    public $contact;
    public $threadId;

    public function __construct(?string $apiKey = null)
    {
        $this->evolution = new EvolutionService();
        // 2. ARMAZENA A API KEY NA PROPRIEDADE DA CLASSE
        $this->apiKey = $apiKey ?? config('services.openai.key');
        
        if (empty($this->apiKey)) {
            throw new \Exception("A chave de API da OpenAI não foi fornecida ou configurada.");
        }

       
        
        // 3. USA A PROPRIEDADE ARMAZENADA PARA CRIAR O CLIENTE
        $this->client = \OpenAI::client($this->apiKey);
    }

    public function processMessage(string $assistantId, ?string $threadId, string $userMessage, $instancia = null, $contact = null): array
    {
        $this->instancia = $instancia;
        $this->contact = $contact;

        if (!empty($threadId)) {
            $tokens = (int) $this->contarTokensEstimadoDoThread($threadId);
           

            if ($tokens > 100000) {
               
                $threadId = null;
            }
        }

        $this->threadId = $threadId;

        // Garantimos que existe um thread 
        if (is_null($threadId)) {
            $thread = $this->client->threads()->create([]);
            $threadId = $thread->id;
           
        }

        // BLOQUEIA processamento concorrente por thread_id
        return Cache::lock("thread_lock_{$threadId}", 60)->block(10, function () use ($assistantId, $threadId, $userMessage) {

           

            // Aguarda Run anterior (se existir) finalizar
            $this->aguardarRunFinalizar($threadId);

            //if(!$aguardarRunFinalizar){$resposta = "Peço desculpas, mas estou com uma dificuldade técnica no momento e não consegui processar sua última mensagem. Por favor, você poderia tentar enviá-la novamente em alguns instantes?";}

            // Adiciona a nova mensagem
            $this->adicionarMensagemAoThread($threadId, $userMessage);

            // Cria e aguarda novo Run
            $this->criarRunEEsperarFinalizar($threadId, $assistantId);

            $resposta = $this->extractAssistantResponse($threadId);
            
            return ['response' => $resposta, 'thread_id' => $threadId,];
            
        });
    }

    public function createThread(): string
    {
       
        $thread = $this->client->threads()->create([]);
        return $thread->id;
    }

    public function submit_outputs(string $run_id, string $thread_id, array $tool_outputs): bool
    {
        try {

            $this->client->threads()->runs()->submitToolOutputs($thread_id,$run_id,['tool_outputs' => $tool_outputs]);

            return true; // ✅ retorno correto
        } catch (\Exception $e) {
            Log::error('OpenAIService:92 - Erro ao submeter outputs para OpenAI', [
                'run_id'    => $run_id,
                'thread_id' => $thread_id,
                'error'     => $e->getMessage(),
            ]);
        }

        return false;
    }



    private function aguardarRunFinalizar(string $threadId, int $timeoutSegundos = 30): bool
    {
        $inicio = time();
        do {
            $runs = $this->client->threads()->runs()->list($threadId);

            $ativo = collect($runs->data)->first(fn($run) =>
                in_array($run->status, ['queued', 'in_progress', 'cancelling', 'requires_action'])
            );

            if (!$ativo) {
                // Nenhum run ativo → terminou com sucesso
                return true;
            }

            sleep(1);
        } while ((time() - $inicio) < $timeoutSegundos);

        // Se ainda tem run ativo depois do timeout → falhou
        return false;
    }


    public function run($threadId){
      return $this->client->threads()->runs()->list($threadId);  
    }

    public function extractRun($threadId, $runId){
       return $this->client->threads()->runs()->retrieve($threadId, $runId);
    }

    private function adicionarMensagemAoThread(string $threadId, string $mensagem): void
    {
        $tentativas = 0;
        $maxTentativas = 3;

        while ($tentativas < $maxTentativas) {
            try {
                $this->client->threads()->messages()->create($threadId, [
                    'role' => 'user',
                    'content' => $mensagem,
                ]);

               
                return; // deu certo, sai da função
            } catch (\Exception $e) {
                $tentativas++;
                

                if ($tentativas >= $maxTentativas) {
                    Log::error("OpenAIService:150 - Erro definitivo ao adicionar mensagem no thread {$threadId} após {$tentativas} tentativas.");
                    throw $e;
                }

                sleep(5); // espera 5 segundos antes da próxima tentativa
            }
        }
    }


    private function criarRunEEsperarFinalizar(string $threadId, string $assistantId, int $maxTentativas = 3)
    {
        $tentativas = 0;
        $run = null;

        while ($tentativas < $maxTentativas) {
            $tentativas++;
           

            $run = $this->client->threads()->runs()->create($threadId, [
                'assistant_id' => $assistantId,
            ]);

            while (in_array($run->status, ['queued', 'in_progress', 'cancelling', 'requires_action'])) {
                
                if ($run->status === 'requires_action') {
                    //if($this->contact=='5562995772922' OR $this->contact=='556295772922'){dd($run);}
                    
                    $tool_outputs = $this->executar_functions($run->toArray());
                    $this->submit_outputs($run->id, $run->threadId, $tool_outputs);
                }
                
                sleep(1);
                
                $run = $this->client->threads()->runs()->retrieve($threadId, $run->id);
            }

            if ($run->status === 'completed') {
               
                return $run;
            }

            
            sleep(2);
        }

        
        throw new \Exception("Não foi possível concluir um Run após {$maxTentativas} tentativas.");
    }

    public function executar_functions($run){
        
       

        foreach($run['required_action']['submit_tool_outputs']['tool_calls'] as $function){

            
            
            $name = $function['function']['name'];
            $arguments = $function['function']['arguments'] ?? null;
            
            
            if($name=='notificar_adm'){
                $tool_outputs[]=[
                "tool_call_id"=> $function['id'],
                "output" => json_encode($this->evolution->notificar_adm($arguments, $this->instancia, $this->contact))
                ];
            }

            if($name=='buscar_get'){
                $dados = json_decode($arguments, true);
                $tool_outputs[]=[
                "tool_call_id"=> $function['id'],
                "output" => json_encode($this->buscar_get($dados['url']))
                ];
            }

            if($name=='enviar_imagem'){
                $dados = json_decode($arguments, true);
                $tool_outputs[]=[
                "tool_call_id"=> $function['id'],
                "output" => json_encode($this->evolution->enviarMedia( $this->contact, $dados['url'], $this->instancia))
                ];
            }

            if($name=='enviar_media'){
                $dados = json_decode($arguments, true);
                $tool_outputs[]=[
                "tool_call_id"=> $function['id'],
                "output" => json_encode($this->evolution->enviarMedia2( $this->contact, $dados['url'], $this->instancia))
                ];
            }

            if($name=='cadastrar_empresas'){
                $tool_outputs[]=[
                "tool_call_id"=> $function['id'],
                "output" => json_encode($this->cadastrar_empresas($arguments))
                ];
            }

            if($name=='encerrarAtendimento'){
                $tool_outputs[]=[
                "tool_call_id"=> $function['id'],
                "output" => json_encode($this->encerrarAtendimento($arguments))
                ];
            }
            

        }
        //if($this->contact=='5562995772922' OR $this->contact=='556295772922'){dd($tool_outputs);}
        return $tool_outputs ?? null;
    }

    public function encerrarAtendimento(){
        // Chat model removed; encerramento manual desativado.
        return "chat feature disabled";
    }

    public function buscar_get($url)
    {
        try {
            $html = file_get_contents($url);

            if (!$html) return null;

            // Remove as tags HTML e limpa espaços extras
            $textoLimpo = strip_tags($html);
            $textoLimpo = preg_replace('/\s+/', ' ', $textoLimpo); // Remove múltiplos espaços
            $textoLimpo = trim($textoLimpo);

            // Limita o tamanho (ex: 200 mil caracteres ≈ 50k tokens)
            $limiteCaracteres = 200000;
            $textoLimitado = mb_substr($textoLimpo, 0, $limiteCaracteres);

            

            //dd($textoLimitado);
            return $textoLimitado;
        } catch (\Exception $e) {
            \Log::error('OpenAIService:247 Erro em buscar_get: ' . $e->getMessage());
            return 'Erro em buscar_get';
        }
    }

    public function cadastrar_empresas(string|array $arguments): array
    {
        // 1. Garantir que está em formato de array
        if (is_string($arguments)) {
            $arguments = json_decode($arguments, true);
        }

        // 2. Validação básica
        if (!isset($arguments['empresas']) || !is_array($arguments['empresas'])) {
            return ['status' => 'erro', 'mensagem' => 'Formato inválido.'];
        }

        $cadastradas = [];
        $ignoradas = [];

        foreach ($arguments['empresas'] as $empresa) {
            $telefone = $empresa['telefone'] ?? null;

            if (!$telefone || LeadEmpresa::where('telefone', $telefone)->exists()) {
                $ignoradas[] = $telefone;
                continue;
            }

            LeadEmpresa::create([
                'nome' => $empresa['nome'] ?? null,
                'segmento' => $empresa['segmento'] ?? null,
                'telefone' => $telefone,
                'cidade' => $empresa['cidade'] ?? null,
                'estado' => $empresa['estado'] ?? null,
            ]);

            $cadastradas[] = $telefone;
        }

        return [
            'status' => 'ok',
            'cadastradas' => $cadastradas,
            'ignoradas' => $ignoradas
        ];
    }

    public function extractAssistantResponse(string $threadId): string
    {
        $messages = $this->client->threads()->messages()->list($threadId, [
            'limit' => 1,
            'order' => 'desc'
        ]);

       

        $resposta = collect($messages->data)
            ->firstWhere('role', 'assistant')
            ->content ?? [];

        $textos = collect($resposta)->filter(fn($c) =>
            $c->type === 'text' && !empty($c->text->value)
        )->pluck('text.value')->toArray();

        return $textos ? implode("\n", $textos) : "Não consegui gerar uma resposta de texto, tente novamente.";
    }

     public function extractAssistantResponse2(string $threadId): string
    {
        $messages = $this->client->threads()->messages()->list($threadId, [
            
            'order' => 'desc'
        ]);

       

        $resposta = collect($messages->data)
            ->firstWhere('role', 'user')
            ->content ?? [];

        $textos = collect($resposta)->filter(fn($c) =>
            $c->type === 'text' && !empty($c->text->value)
        )->pluck('text.value')->toArray();

        return $textos ? implode("\n", $textos) : "Não consegui gerar uma resposta de texto, tente novamente.";
    }

    public function extractThread(string $threadId)
    {
        return $this->client->threads()->messages()->list($threadId, ['order' => 'desc']);

    }


    public function descreverImagemBase64(string $base64): string
    {
            $payload = [
                'model' => 'gpt-4.1-mini',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Descreva esta imagem em português.'],
                            ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,{$base64}"]],
                        ],
                    ],
                ],
                'max_tokens' => 300,
            ];
            
            try {
                $response = $this->client->chat()->create($payload);
                return $response->choices[0]->message->content ?? 'Não foi possível descrever a imagem.';
            } catch (\Exception $e) {
                Log::error("OpenAIService:334 Exceção na chamada de visão da OpenAI: " . $e->getMessage());
                // Retorna a mensagem de erro da API se for um erro específico
                if (method_exists($e, 'getMessage')) {
                    return "Erro da API: " . $e->getMessage();
                }
                return 'Ocorreu um erro ao processar a imagem.';
            }
    }

public function transcreverAudioBase64(string $base64, string $filename = 'audio'): string
{
    // Define os caminhos dos arquivos temporários
    $tmpPath = storage_path('app/tmp/');
    $originalAudioPath = $tmpPath . $filename . '.ogg'; // Salva como .ogg
    $convertedAudioPath = $tmpPath . $filename . '.mp3'; // O alvo da conversão

    try {
        // Garante que o diretório temporário existe
        if (!file_exists($tmpPath)) {
            mkdir($tmpPath, 0777, true);
        }

        // 1. Salva o áudio original recebido do Evolution
        file_put_contents($originalAudioPath, base64_decode($base64));
       

        // 2. Converte o áudio para MP3 usando FFmpeg
       
        
        // Usa o Facade 'Process' do Laravel para executar o comando de forma segura
        $result = Process::run("ffmpeg -i {$originalAudioPath} -acodec libmp3lame -q:a 2 {$convertedAudioPath} -y");
        
        // Verifica se a conversão falhou
        if (!$result->successful()) {
            Log::error("Falha na conversão com FFmpeg.", [
                'exit_code' => $result->exitCode(),
                'output' => $result->output(),
                'error_output' => $result->errorOutput(),
            ]);
            throw new \Exception('Falha ao converter o arquivo de áudio com FFmpeg.');
        }

       

        // 3. Faz a transcrição usando o arquivo MP3 convertido
        $response = $this->client->audio()->transcribe([
            'file' => fopen($convertedAudioPath, 'r'),
            'model' => 'whisper-1', // Recomendo usar o 'whisper-1' para transcrição
            'language' => 'pt'
        ]);
        
        return trim($response->text ?? '') ?: 'Transcrição do áudio vazia.';

    } catch (\Exception $e) {
        Log::error("OpenAIService: Erro na transcrição de áudio: " . $e->getMessage());
        return 'Erro ao transcrever o áudio.';
        
    } finally {
        // 4. Limpa AMBOS os arquivos temporários, não importa o que aconteça
        if (file_exists($originalAudioPath)) {
            unlink($originalAudioPath);
        }
        if (file_exists($convertedAudioPath)) {
            unlink($convertedAudioPath);
        }
       
    }
}

    public function listAssistants(): array
    {
       
        try {
            // O pacote lida com a paginação básica por padrão.
            // O 'data' contém o array de assistentes.
            $assistants = $this->client->assistants()->list(['limit' => 100])->data; // Adicionei 'limit' para pegar mais por padrão
            return $assistants;
        } catch (\Exception | \Throwable $e) { // Adicionei Throwable para pegar mais exceções
            Log::error("OpenAIService:384 - Falha na API da OpenAI ao listar assistentes: " . $e->getMessage());
            throw $e; // Relança a exceção para que o controller possa tratá-la
        }
    }   
    
    public function createAssistant(string $name, string $instructions): \OpenAI\Responses\Assistants\AssistantResponse
    {
       

        try {
            // Usamos o cliente do pacote para criar o assistente
            $assistant = $this->client->assistants()->create([
                'model' => 'gpt-4.1-nano', // Modelo padrão, pode ser configurável no futuro
                'name' => $name,
                'instructions' => $instructions,
                'tools' =>  [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'buscar_get',
                        'description' => 'Busca informações em tempo real de uma URL para responder perguntas que exigem dados atuais.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'url' => [
                                    'type' => 'string',
                                    'description' => 'A URL completa da fonte da informação.'
                                ],
                            ],
                            'required' => ['url'],
                        ],
                    ]
                ],
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'notificar_adm',
                        'description' => 'Notifica um administrador humano quando a conversa precisa de intervenção ou escalonamento.',
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
                        ],
                    ]
                ],
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'enviar_media',
                        'description' => 'Envia vídeo ou imagem usando uma url. Sempre que for necessário enviar uma imagem ou vídeo para um usuário use esta ferramenta.',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'url' => [
                                    'type' => 'string',
                                    'description' => 'A URL da imagem ou vídeo que será enviada.'
                                ],
                            ],
                            'required' => ['url'],
                        ],
                    ]
                ],
            ],
            ]);

           

            return $assistant;

        } catch (\Exception $e) {
            // Captura e registra qualquer erro da API
            Log::error("OpenAIService:448 - Falha na API da OpenAI ao criar assistente '{$name}': " . $e->getMessage());
            // Lança a exceção novamente para que o controller possa tratá-la
            throw $e;
        }
    }

    /**
     * Atualiza um assistente existente na plataforma da OpenAI.
     *
     * @param string $assistantId O ID do assistente a ser modificado.
     * @param array $data Os dados a serem atualizados (ex: ['name' => 'Novo Nome', 'instructions' => '...']).
     * @return \OpenAI\Responses\Assistants\AssistantResponse O objeto do assistente atualizado.
     */
    

   public function contarTokensEstimadoDoThread(string $threadId): int
    {
        try {
            $total = 0;
            $after = null;

            do {
                $params = ['order' => 'asc', 'limit' => 100];
                if ($after) $params['after'] = $after;

                $mensagens = $this->client->threads()->messages()->list($threadId, $params);

                foreach ($mensagens->data as $mensagem) {
                    foreach ($mensagem->content as $conteudo) {
                        if ($conteudo->type === 'text' && !empty($conteudo->text->value)) {
                            $total += ceil(strlen($conteudo->text->value) / 4);
                        }
                    }
                }

                $after = $mensagens->meta->next_id ?? null;
            } while ($after);

            return $total;
        } catch (\Exception $e) {
            Log::error("OpenAIService:502 - Erro ao contar tokens: " . $e->getMessage());
            return 0; // retorna 0 se der erro
        }
    }





}
