<?php

namespace Tests\Unit\Services;

use App\Models\AgencySetting;
use App\Models\GrupoConjuntoMensagem;
use App\Models\User;
use App\Services\GrupoConjuntoActionTimingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GrupoConjuntoActionTimingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_preset_for_user_uses_agency_setting_when_present(): void
    {
        Config::set('services.group_actions_timing.preset', 'standard');

        $user = User::factory()->create();
        AgencySetting::create([
            'user_id' => $user->id,
            'group_action_timing_preset' => 'conservative',
        ]);

        $service = new GrupoConjuntoActionTimingService();

        $this->assertSame('conservative', $service->resolvePresetForUser((int) $user->id));
        $rules = $service->resolveRulesForUser((int) $user->id);
        $this->assertSame(5, $rules['action_interval_min_seconds']);
        $this->assertSame(30, $rules['action_interval_max_seconds']);
        $this->assertSame(10, $rules['connection_max_per_minute']);
    }

    public function test_resolve_preset_for_user_falls_back_to_config(): void
    {
        Config::set('services.group_actions_timing.preset', 'fast');

        $user = User::factory()->create();
        $service = new GrupoConjuntoActionTimingService();

        $this->assertSame('fast', $service->resolvePresetForUser((int) $user->id));
        $rules = $service->resolveRulesForUser((int) $user->id);
        $this->assertSame(5, $rules['action_interval_min_seconds']);
        $this->assertSame(30, $rules['action_interval_max_seconds']);
        $this->assertSame(30, $rules['connection_max_per_minute']);
    }

    public function test_invalid_preset_falls_back_to_standard(): void
    {
        Config::set('services.group_actions_timing.preset', 'invalid-preset');

        $user = User::factory()->create();
        AgencySetting::create([
            'user_id' => $user->id,
            'group_action_timing_preset' => 'abc',
        ]);

        $service = new GrupoConjuntoActionTimingService();
        $this->assertSame('standard', $service->resolvePresetForUser((int) $user->id));
    }

    public function test_all_group_action_types_reserve_interval_between_five_and_thirty_seconds(): void
    {
        Config::set('services.group_actions_timing.enabled', true);
        Config::set('services.group_actions_timing.skip_in_tests', false);
        Config::set('services.group_actions_timing.preset', 'standard');
        Cache::flush();

        $service = new GrupoConjuntoActionTimingService();

        foreach (GrupoConjuntoMensagem::ACTION_TYPES as $index => $actionType) {
            $conexaoId = 9000 + $index;
            $groupJid = "12036315374256102{$index}@g.us";

            $before = microtime(true);
            $this->assertTrue($service->acquireDispatchSlot(1, $conexaoId, $groupJid, $actionType));
            $after = microtime(true);

            $state = Cache::get("gcm_timing:conn:{$conexaoId}:state");
            $this->assertIsArray($state);

            $nextActionAt = (float) ($state['next_action_at'] ?? 0);
            $this->assertGreaterThanOrEqual(4.5, $nextActionAt - $after);
            $this->assertLessThanOrEqual(30.5, $nextActionAt - $before);
        }
    }
}
