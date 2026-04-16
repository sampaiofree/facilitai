<?php

use App\Support\OpenAIConversationFormatter;

test('formatter normaliza apenas mensagens visiveis e concatena textos', function () {
    $messages = OpenAIConversationFormatter::normalizeItems([
        [
            'id' => 'msg_assistant_1',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                [
                    'type' => 'output_text',
                    'text' => "*Joana*\r\nPrimeira linha",
                ],
                [
                    'type' => 'output_text',
                    'text' => 'Segunda linha',
                ],
            ],
        ],
        [
            'id' => 'msg_system_1',
            'type' => 'message',
            'role' => 'system',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => 'Oculto',
                ],
            ],
        ],
        [
            'id' => 'msg_user_1',
            'type' => 'message',
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => 'Olá usuário',
                ],
            ],
        ],
    ]);

    expect($messages)->toHaveCount(2);
    expect($messages[0]['role'])->toBe('assistant');
    expect($messages[0]['text'])->toBe("*Joana*\nPrimeira linha\n\nSegunda linha");
    expect($messages[0]['html'])->toContain('<strong>Joana</strong>');
    expect($messages[1]['role'])->toBe('user');
    expect($messages[1]['text'])->toBe('Olá usuário');
});

test('formatter escapa html e preserva formatação basica estilo whatsapp', function () {
    $formatted = OpenAIConversationFormatter::formatWhatsappText("<script>alert('x')</script>\n*Nome*");

    expect($formatted)->not->toContain('<script>');
    expect($formatted)->toContain('&lt;script&gt;alert');
    expect($formatted)->toContain("\n");
    expect($formatted)->toContain('<strong>Nome</strong>');
});

test('formatter separa mensagens visiveis e itens tecnicos ocultados', function () {
    $partition = OpenAIConversationFormatter::partitionItems([
        [
            'id' => 'msg_system_1',
            'type' => 'message',
            'status' => 'completed',
            'role' => 'system',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => 'Contexto interno',
                ],
            ],
        ],
        [
            'id' => 'reasoning_1',
            'type' => 'reasoning',
            'status' => 'completed',
            'summary' => [],
        ],
        [
            'id' => 'tool_1',
            'type' => 'function_call',
            'status' => 'completed',
            'name' => 'buscar_lead',
        ],
        [
            'id' => 'tool_output_1',
            'type' => 'function_call_output',
            'status' => 'completed',
            'call_id' => 'call_123',
        ],
        [
            'id' => 'msg_assistant_empty',
            'type' => 'message',
            'status' => 'completed',
            'role' => 'assistant',
            'content' => [],
        ],
        [
            'id' => 'msg_user_1',
            'type' => 'message',
            'status' => 'completed',
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => 'Oi',
                ],
            ],
        ],
    ]);

    expect($partition['messages'])->toHaveCount(1);
    expect($partition['messages'][0]['role'])->toBe('user');
    expect($partition['technicalItems'])->toHaveCount(5);
    expect($partition['technicalItems'][0]['label'])->toBe('Mensagem system');
    expect($partition['technicalItems'][1]['label'])->toBe('Reasoning');
    expect($partition['technicalItems'][2]['label'])->toBe('Chamada de funcao');
    expect($partition['technicalItems'][2]['summary'])->toContain('buscar_lead');
    expect($partition['technicalItems'][3]['label'])->toBe('Saida de funcao');
    expect($partition['technicalItems'][4]['summary'])->toContain('sem texto visivel');
    expect($partition['technicalItems'][0]['json'])->toContain('"role": "system"');
});
