<?php

namespace App\Support;

use App\Models\WhatsappCloudCustomField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CustomFieldScope
{
    public static function normalizeFieldName(string $value): string
    {
        $value = Str::ascii($value);
        $value = Str::lower($value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';
        $value = trim($value, '_');

        if ($value === '') {
            $value = 'campo';
        }

        if (preg_match('/^\d/', $value)) {
            $value = 'campo_' . $value;
        }

        return Str::limit($value, 120, '');
    }

    public static function scopeLabel(?string $clienteNome, ?int $clienteId): string
    {
        if (!$clienteId) {
            return 'global legado';
        }

        $clienteNome = trim((string) $clienteNome);

        if ($clienteNome !== '') {
            return preg_match('/^cliente\b/i', $clienteNome) ? $clienteNome : 'Cliente ' . $clienteNome;
        }

        return 'Cliente #' . $clienteId;
    }

    public static function visibleToClienteQuery(int $userId, int $clienteId): Builder
    {
        return WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->where(function (Builder $query) use ($clienteId): void {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteId);
            });
    }

    public static function hasLegacyConflict(int $userId, string $name, ?int $ignoreId = null): bool
    {
        $query = WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->whereNull('cliente_id')
            ->where('name', trim($name));

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public static function legacyConflictMessage(): string
    {
        return 'Já existe um campo global legado com esse nome técnico na sua conta.';
    }

    public static function resolveUniqueFieldName(int $userId, int $clienteId, string $base, ?int $ignoreId = null): string
    {
        $query = static::visibleToClienteQuery($userId, $clienteId);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        $existing = $query->pluck('name')->all();
        if (!in_array($base, $existing, true)) {
            return $base;
        }

        $index = 2;
        do {
            $suffix = (string) $index;
            $trimmedBase = Str::limit($base, max(1, 120 - strlen($suffix)), '');
            $candidate = $trimmedBase . $suffix;
            $index++;
        } while (in_array($candidate, $existing, true));

        return $candidate;
    }
}
