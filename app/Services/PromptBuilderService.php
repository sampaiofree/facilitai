<?php

namespace App\Services;

class PromptBuilderService
{
    /**
     * Constrói um prompt de instruções para a OpenAI a partir dos dados do quiz.
     *
     * @param array $data Os dados validados do formulário do quiz.
     * @return string O prompt de instruções finalizado.
     */
    public function build(array $data): string
    {
        //dd($data); exit;
        
        $telefones="";
        foreach($data['admin_phones'] as $telefone){$telefones.=$telefone.";";}

        // Inicia a construção do prompt com seções claras (usando Markdown)
        $prompt = "## Perfil do Assistente\n";
        $prompt .= "- **Função Principal:** {$data['main_function']}\n";
        $prompt .= "- **Público-Alvo:** Deve se comunicar primariamente com {$data['target_audience']}.\n";
        $prompt .= "- **Tom de Voz:** A comunicação deve ser {$data['tone_of_voice']}.\n\n";
        $prompt .= "- **Mensagem de Saudação:** Quando receber a primeira mensagem, inicie a primeira interação com o usuário com a seguinte frase: \"{$data['first_message']}\"\n\n";

        // Adiciona a seção de informações importantes, se houver
        if (!empty($data['important_info'])) {
            $prompt .= "## Base de Conhecimento Essencial\n";
            $prompt .= "- Use `buscar_get` para qualquer URL mencionada.\n";
            $prompt .= "- Nunca tente adivinhar o conteúdo da página. Sempre use a tool `buscar_get`.\n";
            $prompt .= "As seguintes informações são cruciais para suas respostas. Use-as como fonte primária de verdade:\n";
            $prompt .= $data['important_info'] . "\n"; 
            $prompt .= "\n\n";
        }

        // Adiciona a seção de restrições, se houver
        if (!empty($data['restrictions'])) {
            $prompt .= "## Regras e Restrições Críticas\n";
            $prompt .= "Sob nenhuma circunstância você deve:\n";
            $prompt .= "- {$data['restrictions']}\n\n";
        }
        

        // Adiciona o passo a passo do atendimento
        $prompt .= "## Processo de Atendimento Obrigatório\n";
        $prompt .= "Siga estritamente a seguinte sequência de passos em todas as conversas:\n";
        foreach ($data['step_by_step'] as $index => $step) {
            if (!empty(trim($step))) {
                $prompt .="- ". ($index + 1) . ". " . trim($step) . "\n";
            }
        }
        $prompt .= "- Não repita o processo de atendimento.\n";
        $prompt .= "\n";

       

        // Adiciona as situações específicas, se houver
        if (!empty($data['situations']) && is_array($data['situations'])) {
            $prompt .= "## Tratamento de Situações Específicas\n";
            $prompt .= "Responda a cenários específicos da seguinte maneira:\n";
            foreach ($data['situations'] as $item) {
                if (!empty($item['situation']) && !empty($item['response'])) {
                    $prompt .= "- Se o usuário mencionar algo como \"" . trim($item['situation']) . "\", sua ação deve ser: " . trim($item['response']) . ".\n";
                }
            }
        }

$prompt .= "\n";        

$prompt .= "# Envio de mídia:
- Para enviar audio, PDF, imagem ou vídeo, use `enviar_media` com a URL disponível.
- Não envie links diretos, só via ferramenta.
- Use apenas URLs fornecidas, sem inventar.
\n\n";

$prompt .= "# Atendimento humano:
- Se o usuário pedir humano ou precisar de intervenção, use `notificar_adm`.
- No campo numeros_telefone envie os números {$telefones}.
- Nunca revele o número do administrador.
- Pegue todas as informações possíveis e use `registrar_info_chat`.
\n\n";

$prompt .= "# Uso de agenda:
- **Nunca mencione ou solicite IDs dos horários** ao usuário. Os IDs são apenas para uso interno no uso da ferramenta.
- Não precisa mencionar o horario final, apenas o início.
- Exemplo de formato: “Segunda, 06 de outubro — às 12h.”
\n\n";

$prompt .= "# Padrão de Respostas:
- Use apenas formatação suportada pelo WhatsApp:  
  - *negrito* com asteriscos  
  - _itálico_ com underlines  
- Nunca use Markdown ou [texto](url)
- Máx. 300 caracteres no total.
- Máx. 3 parágrafos curtos, 1 frase por parágrafo.
- Pule uma linha entre parágrafos.
- Use emojis com moderação.\n\n";

        //dd($prompt); exit;

        return $prompt;
    }

}