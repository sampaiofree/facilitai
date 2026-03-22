<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LeadWebhookPayloadMapper
{
    /**
     * @return array<string, scalar|null>
     */
    public function scalarPaths(array $payload): array
    {
        $paths = [];
        $this->flattenScalarPaths($payload, 'payload', $paths);
        ksort($paths);

        return $paths;
    }

    public function resolvePath(array $payload, ?string $path): mixed
    {
        $path = $this->normalizePath($path);
        if ($path === null) {
            return null;
        }

        return data_get(['payload' => $payload], $path);
    }

    public function resolveScalarString(array $payload, ?string $path): ?string
    {
        $value = $this->resolvePath($payload, $path);

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            $value = trim((string) $value);
            return $value === '' ? null : $value;
        }

        return null;
    }

    public function renderTemplate(string $template, array $payload): string
    {
        return preg_replace_callback('/{{\s*(payload(?:\.[^}\s]+)*)\s*}}/', function (array $matches) use ($payload) {
            $value = $this->resolvePath($payload, $matches[1] ?? null);

            if ($value === null) {
                return '';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $encoded === false ? '' : $encoded;
        }, $template) ?? $template;
    }

    public function canonicalJson(array $payload): string
    {
        $normalized = $this->sortRecursive($payload);
        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? '{}' : $encoded;
    }

    public function normalizePath(?string $path): ?string
    {
        $path = is_string($path) ? trim($path) : '';
        if ($path === '') {
            return null;
        }

        if (!Str::startsWith($path, 'payload.')) {
            return null;
        }

        return $path;
    }

    /**
     * @param array<string, scalar|null> $paths
     */
    private function flattenScalarPaths(mixed $value, string $prefix, array &$paths): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->flattenScalarPaths($item, $prefix . '.' . $key, $paths);
            }

            return;
        }

        if ($value === null || is_scalar($value)) {
            $paths[$prefix] = $value;
        }
    }

    private function sortRecursive(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (Arr::isList($value)) {
            return array_map(fn ($item) => $this->sortRecursive($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->sortRecursive($item);
        }

        return $value;
    }
}
