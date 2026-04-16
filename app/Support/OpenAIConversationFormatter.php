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
        $messages = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized = static::normalizeItem($item);
            if ($normalized !== null) {
                $messages[] = $normalized;
            }
        }

        return $messages;
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
}
