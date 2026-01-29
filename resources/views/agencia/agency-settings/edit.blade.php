@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Configurações da agência</h2>
            <p class="text-sm text-slate-500">Atualize os dados públicos e visuais da sua agência.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form method="POST" action="{{ route('agencia.agency-settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="custom_domain">Domínio customizado</label>
                    <input
                        id="custom_domain"
                        name="custom_domain"
                        type="text"
                        value="{{ old('custom_domain', $settings->custom_domain) }}"
                        placeholder="minhaempresa.com.br"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="app_name">Nome da aplicação</label>
                    <input
                        id="app_name"
                        name="app_name"
                        type="text"
                        value="{{ old('app_name', $settings->app_name) }}"
                        placeholder="Minha Agência"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="support_email">E-mail de suporte</label>
                    <input
                        id="support_email"
                        name="support_email"
                        type="email"
                        value="{{ old('support_email', $settings->support_email) }}"
                        placeholder="suporte@empresa.com"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="support_whatsapp">WhatsApp de suporte</label>
                    <input
                        id="support_whatsapp"
                        name="support_whatsapp"
                        type="text"
                        value="{{ old('support_whatsapp', $settings->support_whatsapp) }}"
                        placeholder="+55 11 99999-0000"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="primary_color">Cor primária</label>
                        <input
                            id="primary_color"
                            name="primary_color"
                            type="color"
                            value="{{ old('primary_color', $settings->primary_color ?? '#2563eb') }}"
                            class="mt-1 h-12 w-full rounded-lg border-none p-0 shadow-sm"
                        >
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="secondary_color">Cor secundária</label>
                        <input
                            id="secondary_color"
                            name="secondary_color"
                            type="color"
                            value="{{ old('secondary_color', $settings->secondary_color ?? '#f97316') }}"
                            class="mt-1 h-12 w-full rounded-lg border-none p-0 shadow-sm"
                        >
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="timezone">Timezone</label>
                    <select
                        id="timezone"
                        name="timezone"
                        class="mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Selecione uma timezone</option>
                        @foreach ($timezones as $zone)
                            <option value="{{ $zone }}" @selected(old('timezone', $settings->timezone) === $zone)>{{ $zone }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="locale">Locale</label>
                    <select
                        id="locale"
                        name="locale"
                        class="mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="">Selecione um locale</option>
                        @foreach ($locales as $code => $label)
                            <option value="{{ $code }}" @selected(old('locale', $settings->locale) === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="logo">Logo</label>
                    <input
                        id="logo"
                        name="logo"
                        type="file"
                        accept="image/*"
                        class="mt-1 w-full rounded-lg border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @if ($settings->logo_path)
                        <div class="mt-2 flex flex-col gap-1 text-xs text-slate-500">
                            <span>Atual:</span>
                            <a class="text-blue-600 underline" href="{{ Storage::url($settings->logo_path) }}" target="_blank" rel="noreferrer">ver logo</a>
                            <div class="mt-1 h-24 w-24 overflow-hidden rounded-lg border border-slate-200">
                                <img class="h-full w-full object-contain" src="{{ Storage::url($settings->logo_path) }}" alt="Logo atual">
                            </div>
                        </div>
                    @endif
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="favicon">Favicon</label>
                    <input
                        id="favicon"
                        name="favicon"
                        type="file"
                        accept="image/*"
                        class="mt-1 w-full rounded-lg border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @if ($settings->favicon_path)
                        <div class="mt-2 flex flex-col gap-1 text-xs text-slate-500">
                            <span>Atual:</span>
                            <a class="text-blue-600 underline" href="{{ Storage::url($settings->favicon_path) }}" target="_blank" rel="noreferrer">ver favicon</a>
                            <div class="mt-1 h-12 w-12 overflow-hidden rounded-lg border border-slate-200">
                                <img class="h-full w-full object-contain" src="{{ Storage::url($settings->favicon_path) }}" alt="Favicon atual">
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
@endsection
