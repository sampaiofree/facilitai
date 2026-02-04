<?php

namespace App\Support;

use App\Models\Conexao;

class LogContext
{
    public static function base(array $payload = [], ?Conexao $conexao = null): array
    {
        $context = [
            'conexao_id' => $payload['conexao_id'] ?? $conexao?->id,
            'assistant_id' => $payload['assistant_id'] ?? $conexao?->assistant_id,
            'assistant_lead_id' => $payload['assistant_lead_id'] ?? null,
            'lead_id' => $payload['lead_id'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'event_id' => $payload['event_id'] ?? null,
            'message_type' => $payload['message_type'] ?? null,
            'conversation_id' => $payload['conversation_id'] ?? null,
            'provider' => $payload['provider'] ?? $conexao?->credential?->iaplataforma?->nome ?? null,
            'model' => $payload['assistant_model'] ?? $conexao?->iamodelo?->nome ?? $conexao?->assistant?->modelo ?? null,
        ];

        return self::filter($context);
    }

    public static function jobContext($job): array
    {
        $context = [
            'job' => is_object($job) ? class_basename($job) : null,
        ];

        if (is_object($job) && method_exists($job, 'attempts')) {
            $context['attempt'] = $job->attempts();
        }

        if (is_object($job) && property_exists($job, 'job') && $job->job) {
            $context['job_id'] = method_exists($job->job, 'getJobId') ? $job->job->getJobId() : null;
            $context['queue'] = method_exists($job->job, 'getQueue') ? $job->job->getQueue() : null;
        }

        return self::filter($context);
    }

    public static function merge(array ...$contexts): array
    {
        $merged = [];
        foreach ($contexts as $context) {
            foreach ($context as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private static function filter(array $context): array
    {
        return array_filter($context, function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
