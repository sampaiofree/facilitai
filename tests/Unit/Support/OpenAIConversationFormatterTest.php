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
