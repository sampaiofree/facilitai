<?php

namespace App\Support;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TagScope
{
    public static function scopeKey(int $userId, ?int $clienteId): string
    {
        return $clienteId
            ? sprintf('user:%d:cliente:%d', $userId, $clienteId)
            : sprintf('user:%d:global', $userId);
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

    public static function displayLabel(string $name, ?string $clienteNome, ?int $clienteId): string
    {
        return sprintf('%s (%s)', $name, static::scopeLabel($clienteNome, $clienteId));
    }

    public static function conflictingTagsQuery(int $userId, string $name, ?int $clienteId, ?int $ignoreTagId = null): Builder
    {
        $query = Tag::query()
            ->where('user_id', $userId)
            ->where('name', trim($name));

        if ($clienteId !== null) {
            $query->where(function (Builder $builder) use ($clienteId): void {
                $builder->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteId);
            });
        }

        if ($ignoreTagId !== null) {
            $query->where('id', '!=', $ignoreTagId);
        }

        return $query;
    }

    public static function hasConflict(int $userId, string $name, ?int $clienteId, ?int $ignoreTagId = null): bool
    {
        return static::conflictingTagsQuery($userId, $name, $clienteId, $ignoreTagId)->exists();
    }

    public static function duplicateMessage(?int $clienteId): string
    {
        return $clienteId === null
            ? 'Já existe uma tag com esse nome na sua conta.'
            : 'Já existe uma tag com esse nome para este cliente ou como tag global legada na sua conta.';
    }

    /**
     * @param iterable<mixed> $names
     * @return array{matched: Collection<int, Tag>, missing: Collection<int, string>, conflicts: Collection<int, string>}
     */
    public static function resolveForLead(int $userId, int $clienteId, iterable $names): array
    {
        $normalized = collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return [
                'matched' => collect(),
                'missing' => collect(),
                'conflicts' => collect(),
            ];
        }

        $existing = Tag::query()
            ->where('user_id', $userId)
            ->whereIn('name', $normalized)
            ->where(function (Builder $builder) use ($clienteId): void {
                $builder->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteId);
            })
            ->get(['id', 'name', 'cliente_id']);

        $conflicts = $existing->groupBy('name')
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->keys()
            ->values();

        $matched = $existing
            ->reject(fn (Tag $tag) => $conflicts->contains($tag->name))
            ->values();

        $missing = $normalized
            ->diff($matched->pluck('name')->unique()->values())
            ->diff($conflicts)
            ->values();

        return [
            'matched' => $matched,
            'missing' => $missing,
            'conflicts' => $conflicts,
        ];
    }
}
