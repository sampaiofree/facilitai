<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AgencySetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        View::composer(['layouts.guest', 'homepage.footer'], function ($view) {
            $host = request()->getHost();
            $domain = $this->normalizeDomain($host);
            $settings = null;

            if ($domain) {
                $settings = AgencySetting::where('custom_domain', $domain)->first();
            }

            $whatsappRaw = $settings?->support_whatsapp;
            $whatsappDigits = $whatsappRaw ? preg_replace('/\D/', '', $whatsappRaw) : null;

            $logoUrl = $settings?->logo_path
                ? Storage::disk('public')->url($settings->logo_path)
                : asset('storage/homepage/facilitAI.png');

            $view->with('agencyBranding', [
                'logo_url' => $logoUrl,
                'name' => $settings?->app_name ?: config('app.name'),
                'whatsapp' => $whatsappRaw,
                'whatsapp_url' => $whatsappDigits ? "https://wa.me/{$whatsappDigits}" : null,
            ]);
        });
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
}
