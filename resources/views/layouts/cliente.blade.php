<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        use App\Models\AgencySetting;
        use Illuminate\Support\Facades\Storage;
        $host = request()->getHost();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $agencySettings = null;
        if ($appHost && strcasecmp($host, $appHost) !== 0) {
            $agencySettings = AgencySetting::where('custom_domain', $host)->first();
        } elseif (auth('client')->check()) {
            $agencySettings = AgencySetting::where('user_id', auth('client')->user()->user_id ?? null)->first();
        }
        $faviconUrl = $agencySettings?->favicon_path ? Storage::disk('public')->url($agencySettings->favicon_path) : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <title>@yield('title', 'Cliente')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-900">
    @php
        $menuBg = $agencySettings?->primary_color ?: '#0f172a';
        $logoUrl = $agencySettings?->logo_path
            ? Storage::disk('public')->url($agencySettings->logo_path)
            : null;
        $clienteAuth = auth('client')->user();
        $clientName = $clienteAuth?->nome ?: 'Cliente';
        $clientInitial = trim($clientName) !== '' ? mb_strtoupper(mb_substr(trim($clientName), 0, 1)) : 'C';
        $accountActive = request()->routeIs('cliente.password.*');
        $hasLibraryEntries = false;
        $hasAssistants = false;
        $hasCredentials = false;
        if (auth('client')->check()) {
            $hasLibraryEntries = \App\Models\LibraryEntry::where('cliente_id', auth('client')->id())->exists();
            $hasAssistants = \App\Models\Assistant::where('cliente_id', auth('client')->id())->exists();
            $hasCredentials = \App\Models\Credential::where('cliente_id', auth('client')->id())->exists();
        }
    @endphp
    <div class="min-h-screen">
        @if(auth('client')->check())
            <header class="border-b border-white/10 text-white" style="background-color: {{ $menuBg }};">
                <div class="mx-auto max-w-6xl px-6 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 text-sm font-semibold text-white/80">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo" class="h-8 w-8 rounded-lg bg-white/10 object-contain p-1">
                        @endif
                        <a href="{{ route('cliente.dashboard') }}" class="{{ request()->routeIs('cliente.dashboard') ? 'text-white' : 'hover:text-white' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('cliente.conexoes.index') }}" class="{{ request()->routeIs('cliente.conexoes.*') ? 'text-white' : 'hover:text-white' }}">
                            Conexões
                        </a>
                        <a href="{{ route('cliente.conversas.index') }}" class="{{ request()->routeIs('cliente.conversas.*') ? 'text-white' : 'hover:text-white' }}">
                            Conversas
                        </a>
                        <a href="{{ route('cliente.crm.index') }}" class="{{ request()->routeIs('cliente.crm.*') ? 'text-white' : 'hover:text-white' }}">
                            CRM
                        </a>
                        @if($hasCredentials)
                            <a href="{{ route('cliente.credentials.index') }}" class="{{ request()->routeIs('cliente.credentials.*') ? 'text-white' : 'hover:text-white' }}">
                                Credenciais
                            </a>
                        @endif
                        @if($hasAssistants)
                            <a href="{{ route('cliente.sequences.index') }}" class="{{ request()->routeIs('cliente.sequences.*') ? 'text-white' : 'hover:text-white' }}">
                                Sequencias
                            </a>
                            <a href="{{ route('cliente.assistant.index') }}" class="{{ request()->routeIs('cliente.assistant.*') ? 'text-white' : 'hover:text-white' }}">
                                Assistentes
                            </a>
                            <a href="{{ route('cliente.images.index') }}" class="{{ request()->routeIs('cliente.images.*') ? 'text-white' : 'hover:text-white' }}">
                                Imagens
                            </a>
                            <a href="{{ route('cliente.tags.index') }}" class="{{ request()->routeIs('cliente.tags.*') ? 'text-white' : 'hover:text-white' }}">
                                Tags
                            </a>
                        @endif
                        @if($hasLibraryEntries)
                            <a href="{{ route('cliente.library.index') }}" class="{{ request()->routeIs('cliente.library.*') ? 'text-white' : 'hover:text-white' }}">
                                Library
                            </a>
                        @endif
                    </div>
                    <div class="flex items-center text-sm text-white/80">
                        <x-dropdown align="right" width="w-64" contentClasses="overflow-hidden rounded-xl bg-white py-2">
                            <x-slot name="trigger">
                                <button
                                    type="button"
                                    data-client-account-trigger
                                    class="group inline-flex max-w-[16rem] items-center gap-2 rounded-lg px-2.5 py-1.5 text-left text-sm font-medium transition {{ $accountActive ? 'bg-white/12 text-white' : 'text-white/85 hover:bg-white/8 hover:text-white' }}"
                                >
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white/95">
                                        {{ $clientInitial }}
                                    </span>
                                    <span class="min-w-0 truncate">{{ $clientName }}</span>
                                    <svg class="h-3.5 w-3.5 shrink-0 text-white/55 transition group-hover:text-white/80" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4"/>
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div data-client-account-menu>
                                    <div class="px-4 pb-2 pt-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $clientName }}</p>
                                        <p class="text-xs text-slate-500">Ações da conta</p>
                                    </div>
                                    <div class="my-1 border-t border-slate-100"></div>

                                    <x-dropdown-link
                                        :href="route('cliente.password.edit')"
                                        class="{{ $accountActive ? 'bg-slate-50 font-semibold text-slate-900' : 'text-slate-700' }}"
                                    >
                                        Senha
                                    </x-dropdown-link>

                                    <form method="POST" action="{{ route('cliente.logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50">
                                            Sair
                                        </button>
                                    </form>
                                </div>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </header>
        @endif

        @hasSection('header')
            <header class="bg-white border-b border-slate-200">
                <div class="mx-auto max-w-6xl px-6 py-4">
                    @yield('header')
                </div>
            </header>
        @endif

        <main class="px-6 py-8">
            <div class="mx-auto max-w-6xl">
            @if (session('success'))
                <div class="mb-6 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
