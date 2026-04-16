<?php

namespace App\Support;

class OpenAIConversationFormatter
{
    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, string>>
     */
    public static function normalizeItems(array $items): array
    {
        return static::partitionItems($items)['messages'];
    }

    /**
     * @param array<int, mixed> $items
     * @return array<int, array<string, string>>
     */
    public static function normalizeTechnicalItems(array $items): array
    {
        return static::partitionItems($items)['technicalItems'];
    }

    /**
     * @param array<int, mixed> $items
     * @return array{
     *     messages: array<int, array<string, string>>,
     *     technicalItems: array<int, array<string, string>>
     * }
     */
    public static function partitionItems(array $items): array
    {
        $messages = [];
        $technicalItems = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = static::normalizeItem($item);
            if ($normalized !== null) {
                $messages[] = $normalized;
                continue;
            }

            $technical = static::normalizeTechnicalItem($item);
            if ($technical !== null) {
                $technicalItems[] = $technical;
            }
        }

        return [
            'messages' => $messages,
            'technicalItems' => $technicalItems,
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, string>|null
     */
    public static function normalizeItem(array $item): ?array
    {
        if (($item['type'] ?? null) !== 'message') {
            return null;
        }

        $role = (string) ($item['role'] ?? '');
        if (!in_array($role, ['user', 'assistant'], true)) {
            return null;
        }

        $text = static::extractText($item);
        if ($text === '') {
            return null;
        }

        return [
            'id' => (string) ($item['id'] ?? ''),
            'role' => $role,
            'sender' => $role === 'assistant' ? 'Assistente' : 'Lead',
            'text' => $text,
            'html' => $role === 'assistant'
                ? static::formatWhatsappText($text)
                : static::escapeText($text),
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function extractText(array $item): string
    {
        $content = $item['content'] ?? [];
        if (!is_array($content)) {
            return '';
        }

        $parts = [];

        foreach ($content as $contentItem) {
            if (!is_array($contentItem)) {
                continue;
            }

            $type = (string) ($contentItem['type'] ?? '');
            if ($type !== '' && !in_array($type, ['input_text', 'output_text'], true)) {
                continue;
            }

            $text = $contentItem['text'] ?? null;
            if (!is_string($text)) {
                continue;
            }

            $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
            if ($text === '') {
                continue;
            }

            $parts[] = $text;
        }

        return implode("\n\n", $parts);
    }

    public static function escapeText(string $text): string
    {
        return e(str_replace(["\r\n", "\r"], "\n", $text));
    }

    public static function formatWhatsappText(string $text): string
    {
        $escaped = static::escapeText($text);

        return preg_replace('/\*([^*\n]+)\*/', '<strong>$1</strong>', $escaped) ?? $escaped;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, string>|null
     */
    private static function normalizeTechnicalItem(array $item): ?array
    {
        $type = trim((string) ($item['type'] ?? ''));
        $role = trim((string) ($item['role'] ?? ''));
        $status = trim((string) ($item['status'] ?? ''));

        return [
            'id' => trim((string) ($item['id'] ?? '')),
            'type' => $type,
            'role' => $role,
            'status' => $status,
            'label' => static::buildTechnicalLabel($type, $role),
            'summary' => static::buildTechnicalSummary($item, $type, $role, $status),
            'json' => static::encodeJson($item),
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function buildTechnicalSummary(array $item, string $type, string $role, string $status): string
    {
        $parts = [];
        $name = static::extractTechnicalName($item);

        if ($name !== '') {
            $parts[] = $name;
        }

        if ($type !== '') {
            $parts[] = 'type: ' . $type;
        }

        if ($role !== '') {
            $parts[] = 'role: ' . $role;
        }

        if ($status !== '') {
            $parts[] = 'status: ' . $status;
        }

        if ($type === 'message' && static::extractText($item) === '') {
            $parts[] = 'sem texto visivel';
        }

        if (empty($parts)) {
            return 'Item tecnico ocultado do chat principal.';
        }

        return implode(' · ', $parts);
    }

    private static function buildTechnicalLabel(string $type, string $role): string
    {
        return match ($type) {
            'function_call' => 'Chamada de funcao',
            'function_call_output' => 'Saida de funcao',
            'reasoning' => 'Reasoning',
            'message' => $role !== ''
                ? 'Mensagem ' . $role
                : 'Mensagem tecnica',
            default => $type !== ''
                ? static::humanize($type)
                : 'Item tecnico',
        };
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function extractTechnicalName(array $item): string
    {
        $candidates = [
            $item['name'] ?? null,
            $item['tool_name'] ?? null,
        ];

        $function = $item['function'] ?? null;
        if (is_array($function)) {
            $candidates[] = $function['name'] ?? null;
        }

        $tool = $item['tool'] ?? null;
        if (is_array($tool)) {
            $candidates[] = $tool['name'] ?? null;
        }

        foreach ($candidates as $candidate) {
            if (!is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function encodeJson(array $item): string
    {
        return json_encode(
            $item,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }

    private static function humanize(string $value): string
    {
        $value = trim(str_replace(['-', '_'], ' ', $value));
        if ($value === '') {
            return 'Item tecnico';
        }

        return ucwords($value);
    }
}
