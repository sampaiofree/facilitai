<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionService
{
    public function enviar_msg_evolution($telefone, $mensagem, $instancia)
    {
        $url = config('services.evolution.url') . "/message/sendText/{$instancia}";
        $apiKey = config('services.evolution.key');

        $payload = [
            'number' => $telefone,
            'text'   => $mensagem,
        ];

        try {
            $response = Http::withHeaders(['apiKey' => $apiKey])->post($url, $payload);

            // Se deu certo, retorna o resultado
            if ($response->successful()) {
                return $response->json();
            }

            // Se falhou, tenta novamente removendo o "9" após o DDD
            if ($response->status() >= 400) {
                $telefoneAjustado = $this->removerNoveAposDDD($telefone);

                if ($telefoneAjustado !== $telefone) {
                    $payload['number'] = $telefoneAjustado;
                    $retry = Http::withHeaders(['apiKey' => $apiKey])->post($url, $payload);

                    if ($retry->successful()) {
                        Log::info("✅ Envio bem-sucedido após remover o 9: {$telefoneAjustado}");
                        return $retry->json();
                    }

                    Log::error('🚨 Falha mesmo após remover o 9', [
                        'telefone_original' => $telefone,
                        'telefone_tentado' => $telefoneAjustado,
                        'status' => $retry->status(),
                        'body' => $retry->body(),
                    ]);
                }
            }

            // Se tudo falhar
            Log::error('EvolutionService: Falha ao enviar mensagem', [ 
                'telefone' => $telefone,
                'mensagem' => $mensagem,
                'instancia' => $instancia,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('EvolutionService: Erro na requisição ao Evolution', [
                'exception' => $e->getMessage(),
                'telefone' => $telefone,
                'mensagem' => $mensagem,
                'instancia' => $instancia,
            ]);
        }

        return "Erro ao enviar mensagem para " . $telefone;
    }

    /**
     * Remove o 9 após o DDD (formato brasileiro)
     * Ex: 5548996774890 → 554896774890
     */
    private function removerNoveAposDDD($numero)
    {
        // Captura DDI (55) + DDD + 9 + número
        // Exemplo: 5548996774890 → 554896774890
        return preg_replace('/^(\d{4,5})(9)(\d{8})$/', '$1$3', $numero);
    }


    public function notificar_adm($arguments, $instance, $contact){

        $dados = json_decode($arguments, true);

        // Agora você acessa assim:
        $telefones = $dados['numeros_telefone'];
        $mensagem = $dados['mensagem']. " Referente ao contato: ".$contact;
        
        foreach( $telefones as  $telefone){$return[] = $this->enviar_msg_evolution($telefone, $mensagem, $instance);}

        return $return;

    }
    

    function conectarInstancia($id)
    {
        $response = Http::withHeaders([
            'apikey' => config('services.evolution.key')
        ])->get(config('services.evolution.url')."/instance/connect/{$id}");

        $res = $response->json();

        if(isset($res['base64']) AND !empty($res['base64'])){
            return $res;
        }else{
            $mensagem = "Problema na geração de QR CODE, alterar o CONFIG_SESSION_PHONE_VERSION";
            $this->enviar_msg_evolution('5562995772922', $mensagem, '177');
        }
    }

    function enviarMedia(string $numero, string $mediaUrl, string $instance)
    {
        
        Log::info('Iniciando envio de mídia para Evolution', compact('numero', 'mediaUrl', 'instance'));

        $url = config('services.evolution.url') . "/message/sendMedia/{$instance}";
        $apiKey = config('services.evolution.key');

        Log::info('URL final da API Evolution', ['url' => $url]);
        Log::info('API Key usada', ['apikey' => $apiKey]);

        // Extrair o nome do arquivo
        $fileName = basename(parse_url($mediaUrl, PHP_URL_PATH));
        Log::info('Nome do arquivo extraído da URL', ['fileName' => $fileName]);

        // Detectar o MIME type com base na extensão
        $extensao = pathinfo($fileName, PATHINFO_EXTENSION);
        Log::info('Extensão detectada', ['extensao' => $extensao]);

        

        $mimeTypes = [
            // Imagens
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            // Vídeos
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
            // Documentos
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $mimetype = $mimeTypes[strtolower($extensao)] ?? 'video';
        Log::info('EvolutionService:116 MIME type definido', ['mimetype' => $mimetype]);

        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv'];

        $fileExtension = strtolower($extensao);

        if (in_array($fileExtension, $imageExtensions)) {
            $mediaType = 'image';
        } elseif (in_array($fileExtension, $videoExtensions)) {
            $mediaType = 'video';
        } else {
            $mediaType = 'document';
        }
        Log::info('EvolutionService: mediatype definido', ['mediatype' => $mediaType]);


        

        $dados = [
            'mediatype' => $mediaType,
            'media' => $mediaUrl,
            'fileName' => $fileName,
            //'mimetype' => $mimetype,
            'caption' => '',
            'number' => $numero
        ];

        // Se for MP3 → chama o método de áudio e retorna
        if ($extensao === 'mp3') {
            $url = config('services.evolution.url') . "/message/sendWhatsAppAudio/{$instance}";
            $dados['audio'] = $mediaUrl;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'apikey' => $apiKey
        ])->post($url, $dados);

        if ($response->successful()) {
            $json = $response->json();
            Log::info('EvolutionService:134 Midia enviada');
            return "Midia enviada";
        }

        Log::error('EvolutionService:138 Erro ao enviar Midia para Evolution', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return "Midia não enviada";

        throw new \Exception('Erro ao enviar Midia para Evolution: ' . $response->body());
    }


    function enviarMedia2(string $numero, string $mediaUrl, string $instance)
    {
        
        Log::info('Iniciando envio de mídia para Evolution', compact('numero', 'mediaUrl', 'instance'));

        $url = config('services.evolution.url') . "/message/sendMedia/{$instance}";
        $apiKey = config('services.evolution.key');

        Log::info('URL final da API Evolution', ['url' => $url]);
        Log::info('API Key usada', ['apikey' => $apiKey]);

        // Extrair o nome do arquivo
        $fileName = basename(parse_url($mediaUrl, PHP_URL_PATH));
        Log::info('Nome do arquivo extraído da URL', ['fileName' => $fileName]);

        // Detectar o MIME type com base na extensão
        $extensao = pathinfo($fileName, PATHINFO_EXTENSION);
        Log::info('Extensão detectada', ['extensao' => $extensao]);

        $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv'];

        $fileExtension = strtolower($extensao);

        if (in_array($fileExtension, $imageExtensions)) {
            $mediaType = 'image';
        } elseif (in_array($fileExtension, $videoExtensions)) {
            $mediaType = 'video';
        } else {
            $mediaType = 'document';
        }
        Log::info('EvolutionService: mediatype definido', ['mediatype' => $mediaType]);

        $dados = [
            'mediatype' => $mediaType,
            'media' => $mediaUrl,
            'fileName' => $fileName,
            'caption' => '',
            'number' => $numero
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'apikey' => $apiKey
        ])->post($url, $dados);

        if ($response->successful()) {
            $json = $response->json();
            Log::info('EvolutionService:134 Midia enviada');
            return "Midia enviada";
        }

        Log::error('EvolutionService:138 Erro ao enviar Midia para Evolution', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return "Midia não enviada";

        throw new \Exception('Erro ao enviar Midia para Evolution: ' . $response->body());
    }

 


}
