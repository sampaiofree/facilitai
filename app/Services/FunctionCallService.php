<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class FunctionCallService
{
    protected int $maxIterations;

    public function __construct(int $maxIterations = 5)
    {
        $this->maxIterations = $maxIterations;
    }

    public function process(Response $response, OpenAIService $openAI, array $context, array $handlers, array $options = []): ?Response
    {
        $current = $response;
        $iterations = 0;
        $onAssistantMessage = $options['on_assistant_message'] ?? null;
        $requestOptions = $options['request_options'] ?? [];

        while ($current && $iterations < $this->maxIterations) {
            $payload = $current->json();
            if (!is_array($payload)) {
                return $current;
            }

            $output = $payload['output'] ?? [];
            if (!is_array($output) || empty($output)) {
                return $current;
            }

            $assistantMessages = $this->extractAssistantMessagesBeforeFunctionCall($output);
            if (is_callable($onAssistantMessage)) {
                foreach ($assistantMessages as $message) {
                    $onAssistantMessage($message);
                }
            }

            $toolOutputs = $this->buildToolOutputs($output, $handlers, $context);
            if (empty($toolOutputs)) {
                return $current;
            }

            $conversationId = $context['conversation_id'] ?? null;
            if (!is_string($conversationId) || $conversationId === '') {
                Log::channel('openai')->warning('FunctionCallService missing conversation id');
                return $current;
            }

            $payload = [
                'model' => $context['model'] ?? 'gpt-4.1-mini',
                'input' => $toolOutputs,
                'conversation' => $conversationId,
            ];

            $current = $openAI->createResponse($payload, $requestOptions);
            if (!$current) {
                return null;
            }

            $iterations++;
        }

        if ($iterations >= $this->maxIterations) {
            Log::channel('openai')->warning('FunctionCallService max iterations reached', [
                'conversation_id' => $context['conversation_id'] ?? null,
            ]);
        }

        return $current;
    }

    protected function buildToolOutputs(array $output, array $handlers, array $context): array
    {
        $toolOutputs = [];

        foreach ($output as $item) {
            if (($item['type'] ?? null) !== 'function_call') {
                continue;
            }

            $name = $item['name'] ?? null;
            $callId = $item['call_id'] ?? $item['id'] ?? null;
            if (!is_string($name) || $name === '' || !is_string($callId) || $callId === '') {
                continue;
            }

            $arguments = $this->parseArguments($item['arguments'] ?? null);
            if (!is_array($arguments)) {
                $toolOutputs[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => 'Argumentos inválidos para a chamada da função.',
                ];
                continue;
            }

            $handler = $handlers[$name] ?? null;
            if (!is_callable($handler)) {
                $toolOutputs[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => "Função {$name} não suportada.",
                ];
                continue;
            }

            try {
                $result = $handler($arguments, $context);
            } catch (\Throwable $e) {
                Log::channel('openai')->error('FunctionCallService handler exception', [
                    'function' => $name,
                    'error' => $e->getMessage(),
                ]);
                $result = 'Erro ao executar a função.';
            }

            if (is_array($result) && array_key_exists('output', $result)) {
                $result = $result['output'];
            }

            if ($result === null) {
                $result = 'Nenhuma resposta retornada pela função.';
            }

            $toolOutputs[] = [
                'type' => 'function_call_output',
                'call_id' => $callId,
                'output' => (string) $result,
            ];
        }

        return $toolOutputs;
    }

    protected function parseArguments($rawArguments): ?array
    {
        if (is_array($rawArguments)) {
            return $rawArguments;
        }

        if (is_string($rawArguments) && $rawArguments !== '') {
            $decoded = json_decode($rawArguments, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    protected function extractAssistantMessagesBeforeFunctionCall(array $output): array
    {
        $messages = [];
        foreach ($output as $item) {
            if (($item['type'] ?? null) === 'function_call') {
                break;
            }
            if (($item['type'] ?? null) === 'message' && ($item['role'] ?? null) === 'assistant') {
                $text = $item['content'][0]['text'] ?? null;
                if (is_string($text) && $text !== '') {
                    $messages[] = $text;
                }
            }
        }

        return $messages;
    }
}
