<?php

use App\Support\GrupoConjuntoFailurePresenter;

test('formata codigos http como mensagens amigaveis', function (int $status, string $expected) {
    $presenter = new GrupoConjuntoFailurePresenter;

    expect($presenter->friendlyMessage('conteúdo bruto secreto', $status))->toBe($expected);
})->with([
    'sem autorização' => [403, 'A conexão não está autorizada para executar esta ação. Verifique a conexão e tente novamente.'],
    'grupo ausente' => [404, 'O grupo não foi encontrado ou não está mais disponível.'],
    'timeout' => [504, 'A integração demorou demais para responder. Tente novamente em alguns instantes.'],
    'dados rejeitados' => [422, 'A integração rejeitou os dados enviados. Revise a ação e tente novamente.'],
    'limite temporário' => [429, 'O limite temporário de requisições foi atingido. Tente novamente em alguns instantes.'],
    'indisponibilidade' => [503, 'A integração do WhatsApp está temporariamente indisponível. Tente novamente mais tarde.'],
]);

test('nao expoe mensagem inesperada recebida da integracao', function () {
    $presenter = new GrupoConjuntoFailurePresenter;

    expect($presenter->friendlyMessage('token=segredo /var/www/app.php:123'))
        ->toBe(GrupoConjuntoFailurePresenter::UNKNOWN_FAILURE_MESSAGE);
});

test('explica quando a conexao nao e administradora do grupo', function () {
    $presenter = new GrupoConjuntoFailurePresenter;

    expect($presenter->friendlyMessage('Forbidden: sender is not an admin', 403))
        ->toBe('A conexão não tem permissão para executar esta ação no grupo. Confirme que o número conectado é administrador do grupo.');
});
