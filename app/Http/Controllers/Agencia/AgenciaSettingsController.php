<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\AgencySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgenciaSettingsController extends Controller
{
    /**
     * Exibe o formulário de configurações da agência.
     */
    public function edit(): View
    {
        $userId = Auth::id();
        $settings = AgencySetting::firstOrNew(['user_id' => $userId]);

        return view('agencia.agency-settings.edit', [
            'settings' => $settings,
            'timezones' => $this->timezoneOptions(),
            'locales' => $this->localeOptions(),
        ]);
    }

    /**
     * Salva/atualiza as configurações da agência.
     */
    public function update(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        $request->merge([
            'custom_domain' => $this->normalizeDomain($request->input('custom_domain')),
        ]);

        $validated = $request->validate([
            'custom_domain' => ['nullable', 'string', 'max:255', 'unique:agency_settings,custom_domain,' . $userId . ',user_id'],
            'app_name' => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_whatsapp' => ['nullable', 'string', 'max:50'],
            'primary_color' => ['nullable', 'string', 'max:20', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'secondary_color' => ['nullable', 'string', 'max:20', 'regex:/^#([0-9a-fA-F]{3}){1,2}$/'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'locale' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
        ], [
            'primary_color.regex' => 'Cor primária deve estar no formato HEX (ex: #1a2b3c).',
            'secondary_color.regex' => 'Cor secundária deve estar no formato HEX (ex: #1a2b3c).',
        ]);

        $payload = [
            'custom_domain' => $request->filled('custom_domain') ? $validated['custom_domain'] : null,
            'app_name' => $validated['app_name'] ?? null,
            'support_email' => $validated['support_email'] ?? null,
            'support_whatsapp' => $validated['support_whatsapp'] ?? null,
            'primary_color' => $validated['primary_color'] ?? null,
            'secondary_color' => $validated['secondary_color'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
            'locale' => $validated['locale'] ?? null,
        ];

        $settings = AgencySetting::firstOrCreate(['user_id' => $userId]);

        // Faz upload do logo e favicon quando enviados.
        if ($request->hasFile('logo')) {
            $payload['logo_path'] = $this->storeAsset($request->file('logo'), $settings->logo_path, $userId, 'logo');
        }
        if ($request->hasFile('favicon')) {
            $payload['favicon_path'] = $this->storeAsset($request->file('favicon'), $settings->favicon_path, $userId, 'favicon');
        }

        $settings->fill($payload);
        $settings->save();

        return redirect()
            ->route('agencia.agency-settings.edit')
            ->with('success', 'Configurações atualizadas com sucesso.');
    }

    private function normalizeDomain(?string $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('#^https?://#i', '', $value);
        $value = preg_replace('#/.*$#', '', $value);
        $value = preg_replace('/:\d+$/', '', $value);
        $value = preg_replace('/^www\./i', '', $value);

        return $value !== '' ? Str::lower($value) : null;
    }

    /**
     * Armazena arquivos de identidade visual e remove o anterior quando existir.
     */
    private function storeAsset($file, ?string $previousPath, int $userId, string $type): string
    {
        $disk = Storage::disk('public');
        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $filename = "{$type}_" . Str::uuid() . ($extension ? ".{$extension}" : '');
        $path = "agency-settings/{$userId}/{$filename}";

        $disk->putFileAs("agency-settings/{$userId}", $file, $filename);

        if ($previousPath && $disk->exists($previousPath)) {
            $disk->delete($previousPath);
        }

        return $path;
    }

    private function timezoneOptions(): array
    {
        $zones = timezone_identifiers_list();
        return collect($zones)->mapWithKeys(function ($zone) {
            return [$zone => $zone];
        })->toArray();
    }

    private function localeOptions(): array
    {
        return [
            'pt_BR' => 'Português (Brasil)',
            'en_US' => 'English (US)',
            'es_ES' => 'Español (España)',
            'fr_FR' => 'Français (France)',
        ];
    }
}
