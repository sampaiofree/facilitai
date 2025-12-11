<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionService
{
    public function enviar_msg_evolution($telefone, $mensagem, $instancia)
    {
        $partes = $this->splitMensagem((string) $mensagem);
        $totalPartes = count($partes);
        $resultados = [];

        foreach ($partes as $indice => $texto) {
            $texto = trim($texto);
            if ($texto === '') {
                continue;
            }

            $resultados[] = $this->enviarSegmentoTexto(
                $telefone,
                $texto,
                $instancia,
                $indice + 1,
                $totalPartes
            );
        }

        if (empty($resultados)) {
            return "Mensagem vazia, nada enviado para {$telefone}";
        }

        return $totalPartes === 1 ? $resultados[0] : $resultados;
    }

    private function enviarSegmentoTexto($telefone, $mensagem, $instancia, $parte, $total)
    {
        $url = config('services.evolution.url') . "/message/sendText/{$instancia}";
        $apiKey = config('services.evolution.key');

        $payload = [
            'number' => $telefone,
            'text'   => $mensagem,
        ];

        try {
            $response = Http::withHeaders(['apiKey' => $apiKey])->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() >= 400) {
                $telefoneAjustado = $this->removerNoveAposDDD($telefone);

                if ($telefoneAjustado !== $telefone) {
                    $payload['number'] = $telefoneAjustado;
                    $retry = Http::withHeaders(['apiKey' => $apiKey])->post($url, $payload);

                    if ($retry->successful()) {
                        Log::info("Envio bem-sucedido apos remover o 9: {$telefoneAjustado}");
                        return $retry->json();
                    }

                    Log::error('Falha mesmo apos remover o 9', [
                        'telefone_original' => $telefone,
                        'telefone_tentado' => $telefoneAjustado,
                        'status' => $retry->status(),
                        'body' => $retry->body(),
                        'parte' => $parte,
                        'total_partes' => $total,
                    ]);
                }
            }

            Log::error('EvolutionService: Falha ao enviar mensagem', [
                'telefone' => $telefone,
                'mensagem' => $mensagem,
                'instancia' => $instancia,
                'status' => $response->status(),
                'body' => $response->body(),
                'parte' => $parte,
                'total_partes' => $total,
            ]);
        } catch (\Exception $e) {
            Log::error('EvolutionService: Erro na requisicao ao Evolution', [
                'exception' => $e->getMessage(),
                'telefone' => $telefone,
                'mensagem' => $mensagem,
                'instancia' => $instancia,
                'parte' => $parte,
                'total_partes' => $total,
            ]);
        }

        return "Erro ao enviar mensagem para " . $telefone;
    }

    /**
     * Remove o 9 apos o DDD (formato brasileiro)
     * Ex: 5548996774890 -> 554896774890
     */
    private function removerNoveAposDDD($numero)
    {
        return preg_replace('/^(\d{4,5})(9)(\d{8})$/', '$1$3', $numero);
    }

    public function notificar_adm($arguments, $instance, $contact)
    {
        $dados = json_decode($arguments, true);

        $telefones = $dados['numeros_telefone'];
        $mensagem = $dados['mensagem'] . " Referente ao contato: " . $contact;

        foreach ($telefones as $telefone) {
            $return[] = $this->enviar_msg_evolution($telefone, $mensagem, $instance);
        }

        return $return ?? [];
    }

    public function reiniciarInstancia(string $instancia)
    {
        $url = config('services.evolution.url') . "/instance/restart/{$instancia}";
        $apiKey = config('services.evolution.key');

        try {
            // A API espera um POST para este endpoint; o PUT estava retornando 404.
            $response = Http::withHeaders(['apiKey' => $apiKey])->post($url);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao reiniciar instancia', [
                'instancia' => $instancia,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro na requisicao de restart', [
                'instancia' => $instancia,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao reiniciar instancia {$instancia}";
    }

    /**
     * Envia presen├ºa (digitando/gravando) para um contato.
     */
    public function enviarPresenca(string $instance, string $numero, ?string $presence = null)
    {
        $url = config('services.evolution.url') . "/chat/sendPresence/{$instance}";
        $apiKey = config('services.evolution.key');

        $presence = in_array($presence, ['composing', 'recording'], true) ? $presence : 'composing';
        $delayMs = 7000; // delay padr├úo em milissegundos (5s)

        $payload = [
            'number' => $numero,
            'presence' => $presence,
            'delay' => $delayMs,
            'options' => [
                'presence' => $presence,
                'delay' => $delayMs,
                'number' => $numero,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $apiKey,
            ])->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('EvolutionService: falha ao enviar presenca', [
                'instance' => $instance,
                'numero' => $numero,
                'presence' => $presence,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('EvolutionService: erro ao enviar presenca', [
                'instance' => $instance,
                'numero' => $numero,
                'presence' => $presence,
                'exception' => $e->getMessage(),
            ]);
        }

        return "Erro ao enviar presenca para {$numero}";
    }

    function conectarInstancia($id)
    {
        $response = Http::withHeaders([
            'apikey' => config('services.evolution.key')
        ])->get(config('services.evolution.url') . "/instance/connect/{$id}");

        $res = $response->json();

        if (isset($res['base64']) and !empty($res['base64'])) {
            return $res;
        } else {
            $mensagem = "Problema na geracao de QR CODE, alterar o CONFIG_SESSION_PHONE_VERSION";
            $this->enviar_msg_evolution('5562995772922', $mensagem, '177');
        }
    }

    function enviarMedia(string $numero, string $mediaUrl, string $instance)
    {
        Log::info('Iniciando envio de midia para Evolution', compact('numero', 'mediaUrl', 'instance'));

        $url = config('services.evolution.url') . "/message/sendMedia/{$instance}";
        $apiKey = config('services.evolution.key');

        Log::info('URL final da API Evolution', ['url' => $url]);
        Log::info('API Key usada', ['apikey' => $apiKey]);

        $fileName = basename(parse_url($mediaUrl, PHP_URL_PATH));
        Log::info('Nome do arquivo extraido da URL', ['fileName' => $fileName]);

        $extensao = pathinfo($fileName, PATHINFO_EXTENSION);
        Log::info('Extensao detectada', ['extensao' => $extensao]);

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska',
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
            'caption' => '',
            'number' => $numero
        ];

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

        return "Midia nao enviada";

        throw new \Exception('Erro ao enviar Midia para Evolution: ' . $response->body());
    }

    function enviarMedia2(string $numero, string $mediaUrl, string $instance)
    {
        Log::info('Iniciando envio de midia para Evolution', compact('numero', 'mediaUrl', 'instance'));

        $url = config('services.evolution.url') . "/message/sendMedia/{$instance}";
        $apiKey = config('services.evolution.key');

        Log::info('URL final da API Evolution', ['url' => $url]);
        Log::info('API Key usada', ['apikey' => $apiKey]);

        $fileName = basename(parse_url($mediaUrl, PHP_URL_PATH));
        Log::info('Nome do arquivo extraido da URL', ['fileName' => $fileName]);

        $extensao = pathinfo($fileName, PATHINFO_EXTENSION);
        Log::info('Extensao detectada', ['extensao' => $extensao]);

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

        return "Midia nao enviada";

        throw new \Exception('Erro ao enviar Midia para Evolution: ' . $response->body());
    }

    private function splitMensagem(string $mensagem, int $limite = 1800): array
    {
        // Converte sequências literais ("\n", "\r") em quebras reais antes de normalizar
        $mensagem = str_replace(["\\r\\n", "\\r", "\\n"], "\n", $mensagem);
        $mensagem = trim(str_replace(["\r\n", "\r"], "\n", $mensagem));
        if ($mensagem === '') {
            return [''];
        }

        // Divide em parágrafos quando houver ao menos uma linha em branco entre eles
        $paragrafos = preg_split('/\n\s*\n/', $mensagem) ?: [$mensagem];

        $resultados = [];

        foreach ($paragrafos as $paragrafo) {
            $paragrafo = trim($paragrafo);
            if ($paragrafo === '') {
                continue;
            }

            if (mb_strlen($paragrafo) > $limite) {
                $resultados = array_merge($resultados, $this->quebrarParagrafoGrande($paragrafo, $limite));
            } else {
                $resultados[] = $paragrafo;
            }
        }

        return $resultados ?: [$mensagem];
    }

    private function quebrarParagrafoGrande(string $paragrafo, int $limite): array
    {
        $palavras = preg_split('/\s+/', $paragrafo) ?: [];
        $blocos = [];
        $linha = '';

        foreach ($palavras as $palavra) {
            $candidato = $linha === '' ? $palavra : $linha . ' ' . $palavra;

            if (mb_strlen($palavra) > $limite) {
                $blocos = array_merge($blocos, $this->quebrarPalavraLonga($palavra, $limite));
                $linha = '';
                continue;
            }

            if (mb_strlen($candidato) > $limite) {
                if ($linha !== '') {
                    $blocos[] = $linha;
                }
                $linha = $palavra;
            } else {
                $linha = $candidato;
            }
        }

        if ($linha !== '') {
            $blocos[] = $linha;
        }

        return $blocos;
    }

    private function quebrarPalavraLonga(string $palavra, int $limite): array
    {
        $pedacos = [];
        $tamanho = mb_strlen($palavra);

        for ($i = 0; $i < $tamanho; $i += $limite) {
            $pedacos[] = mb_substr($palavra, $i, $limite);
        }

        return $pedacos;
    }
}
