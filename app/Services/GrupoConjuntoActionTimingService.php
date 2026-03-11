<?php

namespace App\Services;

use App\Models\AgencySetting;
use App\Models\GrupoConjuntoMensagem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GrupoConjuntoActionTimingService
{
    private const PRESET_RULES = [
        'fast' => [
            'send_interval_seconds' => 1,
            'update_interval_seconds' => 4,
            'jitter_min_seconds' => 0,
            'jitter_max_seconds' => 1,
            'group_cooldown_seconds' => 15,
            'group_image_cooldown_seconds' => 30,
            'connection_max_per_minute' => 30,
        ],
        'standard' => [
            'send_interval_seconds' => 3,
            'update_interval_seconds' => 8,
            'jitter_min_seconds' => 0,
            'jitter_max_seconds' => 2,
            'group_cooldown_seconds' => 30,
            'group_image_cooldown_seconds' => 60,
            'connection_max_per_minute' => 15,
        ],
        'conservative' => [
            'send_interval_seconds' => 5,
            'update_interval_seconds' => 12,
            'jitter_min_seconds' => 1,
            'jitter_max_seconds' => 4,
            'group_cooldown_seconds' => 45,
            'group_image_cooldown_seconds' => 90,
            'connection_max_per_minute' => 10,
        ],
    ];

    private const BACKOFF_SECONDS = [
        1 => 30,
        2 => 90,
        3 => 180,
    ];

    public static function presetOptions(): array
    {
        return [
            'fast' => 'Rápido',
            'standard' => 'Padrão',
            'conservative' => 'Conservador',
        ];
    }

    public function acquireDispatchSlot(int $userId, int $conexaoId, string $groupJid, string $actionType): bool
    {
        if ($this->shouldBypass()) {
            return true;
        }

        $rules = $this->resolveRulesForUser($userId);
        $maxWaitSeconds = $this->resolveMaxWaitSeconds();
        $deadline = microtime(true) + $maxWaitSeconds;
        $attempt = 0;

        while (true) {
            $waitSeconds = $this->computeWaitAndReserveSlot(
                $conexaoId,
                $groupJid,
                $actionType,
                $rules
            );

            if ($waitSeconds <= 0) {
                return true;
            }

            $attempt++;
            if ($attempt > 100) {
                return false;
            }

            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                return false;
            }

            $sleepSeconds = min($waitSeconds, max(0.05, $remaining));
            usleep((int) ceil($sleepSeconds * 1_000_000));
        }
    }

    public function registerRemoteStatus(int $conexaoId, int $httpStatus): void
    {
        if ($this->shouldBypass() || $httpStatus <= 0) {
            return;
        }

        $this->withConnectionLock($conexaoId, function () use ($conexaoId, $httpStatus): void {
            $state = $this->loadConnectionState($conexaoId);
            $now = microtime(true);

            if ($httpStatus === 429 || $httpStatus >= 500) {
                $errorStreak = min(((int) ($state['error_streak'] ?? 0)) + 1, 3);
                $backoff = self::BACKOFF_SECONDS[$errorStreak] ?? 180;

                $state['error_streak'] = $errorStreak;
                $state['next_action_at'] = max((float) ($state['next_action_at'] ?? 0), $now + $backoff);
            } else {
                $state['error_streak'] = 0;
            }

            $this->persistConnectionState($conexaoId, $state);
        });
    }

    public function resolvePresetForUser(int $userId): string
    {
        $settingPreset = '';
        try {
            $settingPreset = trim((string) AgencySetting::query()
                ->where('user_id', $userId)
                ->value('group_action_timing_preset'));
        } catch (\Throwable) {
            $settingPreset = '';
        }

        $defaultPreset = trim((string) config('services.group_actions_timing.preset', 'standard'));
        $preset = $settingPreset !== '' ? $settingPreset : $defaultPreset;

        return array_key_exists($preset, self::PRESET_RULES) ? $preset : 'standard';
    }

    public function resolveRulesForUser(int $userId): array
    {
        $preset = $this->resolvePresetForUser($userId);

        return self::PRESET_RULES[$preset] ?? self::PRESET_RULES['standard'];
    }

    private function computeWaitAndReserveSlot(
        int $conexaoId,
        string $groupJid,
        string $actionType,
        array $rules
    ): float {
        return (float) $this->withConnectionLock($conexaoId, function () use ($conexaoId, $groupJid, $actionType, $rules): float {
            $now = microtime(true);
            $state = $this->loadConnectionState($conexaoId);
            $groupStateKey = $this->groupStateKey($conexaoId, $groupJid);

            $history = array_values(array_filter(
                (array) ($state['history'] ?? []),
                static fn ($timestamp): bool => is_numeric($timestamp) && (float) $timestamp >= ($now - 60)
            ));
            sort($history);

            $maxPerMinute = max(1, (int) ($rules['connection_max_per_minute'] ?? 15));
            $waitByRateLimit = 0.0;
            if (count($history) >= $maxPerMinute) {
                $first = (float) $history[0];
                $waitByRateLimit = max(0.0, ($first + 60) - $now);
            }

            $nextActionAt = (float) ($state['next_action_at'] ?? 0);
            $waitByConnection = max(0.0, $nextActionAt - $now);

            $groupLastAt = (float) Cache::get($groupStateKey, 0);
            $groupCooldown = $actionType === GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_IMAGE
                ? max(1, (int) ($rules['group_image_cooldown_seconds'] ?? 60))
                : max(1, (int) ($rules['group_cooldown_seconds'] ?? 30));
            $waitByGroupCooldown = max(0.0, ($groupLastAt + $groupCooldown) - $now);

            $wait = max($waitByRateLimit, $waitByConnection, $waitByGroupCooldown);
            if ($wait > 0) {
                return $wait;
            }

            $baseInterval = in_array($actionType, [
                GrupoConjuntoMensagem::ACTION_SEND_TEXT,
                GrupoConjuntoMensagem::ACTION_SEND_MEDIA,
            ], true)
                ? max(0, (int) ($rules['send_interval_seconds'] ?? 3))
                : max(0, (int) ($rules['update_interval_seconds'] ?? 8));

            $jitterMin = max(0, (int) ($rules['jitter_min_seconds'] ?? 0));
            $jitterMax = max($jitterMin, (int) ($rules['jitter_max_seconds'] ?? 0));
            $jitter = $jitterMax > 0 ? random_int($jitterMin, $jitterMax) : 0;

            $history[] = $now;
            $state['history'] = $history;
            $state['next_action_at'] = $now + $baseInterval + $jitter;
            $state['error_streak'] = 0;

            $this->persistConnectionState($conexaoId, $state);
            Cache::put($groupStateKey, $now, now()->addHours(2));

            return 0.0;
        });
    }

    private function withConnectionLock(int $conexaoId, callable $callback): mixed
    {
        $lock = Cache::lock($this->connectionLockKey($conexaoId), 10);
        $acquired = false;

        try {
            $acquired = $lock->get() || (bool) $lock->block(10);
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao aplicar lock de timing para grupo.', [
                'conexao_id' => $conexaoId,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            return $callback();
        } finally {
            if ($acquired) {
                optional($lock)->release();
            }
        }
    }

    private function loadConnectionState(int $conexaoId): array
    {
        $state = Cache::get($this->connectionStateKey($conexaoId), []);

        if (!is_array($state)) {
            return [
                'history' => [],
                'next_action_at' => 0,
                'error_streak' => 0,
            ];
        }

        return [
            'history' => (array) ($state['history'] ?? []),
            'next_action_at' => (float) ($state['next_action_at'] ?? 0),
            'error_streak' => (int) ($state['error_streak'] ?? 0),
        ];
    }

    private function persistConnectionState(int $conexaoId, array $state): void
    {
        Cache::put($this->connectionStateKey($conexaoId), $state, now()->addHours(2));
    }

    private function shouldBypass(): bool
    {
        $enabled = (bool) config('services.group_actions_timing.enabled', true);
        if (!$enabled) {
            return true;
        }

        $skipInTests = (bool) config('services.group_actions_timing.skip_in_tests', true);

        return $skipInTests && app()->runningUnitTests();
    }

    private function resolveMaxWaitSeconds(): float
    {
        if (app()->runningInConsole()) {
            $value = (float) config('services.group_actions_timing.max_wait_seconds_worker', 20);
            return max(0.5, $value);
        }

        $value = (float) config('services.group_actions_timing.max_wait_seconds_web', 2.5);

        return max(0.5, $value);
    }

    private function connectionLockKey(int $conexaoId): string
    {
        return "gcm_timing:conn:{$conexaoId}:lock";
    }

    private function connectionStateKey(int $conexaoId): string
    {
        return "gcm_timing:conn:{$conexaoId}:state";
    }

    private function groupStateKey(int $conexaoId, string $groupJid): string
    {
        return 'gcm_timing:conn:' . $conexaoId . ':group:' . md5($groupJid);
    }
}
