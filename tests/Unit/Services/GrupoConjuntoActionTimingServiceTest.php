<?php

namespace Tests\Unit\Services;

use App\Models\AgencySetting;
use App\Models\User;
use App\Services\GrupoConjuntoActionTimingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertSame(12, $rules['update_interval_seconds']);
        $this->assertSame(10, $rules['connection_max_per_minute']);
    }

    public function test_resolve_preset_for_user_falls_back_to_config(): void
    {
        Config::set('services.group_actions_timing.preset', 'fast');

        $user = User::factory()->create();
        $service = new GrupoConjuntoActionTimingService();

        $this->assertSame('fast', $service->resolvePresetForUser((int) $user->id));
        $rules = $service->resolveRulesForUser((int) $user->id);
        $this->assertSame(1, $rules['send_interval_seconds']);
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
}
