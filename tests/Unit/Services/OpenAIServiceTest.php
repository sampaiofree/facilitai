<?php

use App\Services\OpenAIService;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\Response;

function openAiServiceMakeResponse(int $status, array $body = []): Response
{
    return new Response(new Psr7Response(
        $status,
        ['Content-Type' => 'application/json'],
        json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ));
}

test('perfil lento de retry e usado para status 429', function () {
    $service = new OpenAIService('test-key');

    $result = (fn (?Response $response) => $this->shouldUseSlowRetryProfile($response))
        ->call($service, openAiServiceMakeResponse(429));

    expect($result)->toBeTrue();
});

test('perfil lento de retry e usado para rate_limit_exceeded', function () {
    $service = new OpenAIService('test-key');

    $result = (fn (?Response $response) => $this->shouldUseSlowRetryProfile($response))
        ->call($service, openAiServiceMakeResponse(400, [
            'error' => ['code' => 'rate_limit_exceeded'],
        ]));

    expect($result)->toBeTrue();
});

test('perfil lento de retry e usado para conversation_locked', function () {
    $service = new OpenAIService('test-key');

    $result = (fn (?Response $response) => $this->shouldUseSlowRetryProfile($response))
        ->call($service, openAiServiceMakeResponse(409, [
            'error' => ['code' => 'conversation_locked'],
        ]));

    expect($result)->toBeTrue();
});

test('perfil lento de retry nao e usado para 500 503 408 e resposta nula', function () {
    $service = new OpenAIService('test-key');

    $check = fn (?Response $response) => (fn (?Response $currentResponse) => $this->shouldUseSlowRetryProfile($currentResponse))
        ->call($service, $response);

    expect($check(openAiServiceMakeResponse(500)))->toBeFalse();
    expect($check(openAiServiceMakeResponse(503)))->toBeFalse();
    expect($check(openAiServiceMakeResponse(408)))->toBeFalse();
    expect($check(null))->toBeFalse();
});

test('perfil lento eleva delays para 5s e 30s', function () {
    $service = new OpenAIService('test-key');

    $profile = (fn (?Response $response, int $baseDelayMs, int $maxDelayMs) => $this->retryDelayProfile($response, $baseDelayMs, $maxDelayMs))
        ->call($service, openAiServiceMakeResponse(429), 1000, 8000);

    expect($profile)->toBe([5000, 30000]);
});
