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
    <style>[x-cloak] { display: none !important; }</style>
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
        $toolsActive = request()->routeIs('cliente.mensagens-agendadas.*')
            || request()->routeIs('cliente.campos-personalizados.*')
            || request()->routeIs('cliente.webhook-links.*')
            || request()->routeIs('cliente.sequences.*')
            || request()->routeIs('cliente.images.*')
            || request()->routeIs('cliente.tags.*');
        $hasLibraryEntries = false;
        $hasAssistants = false;
        $hasCredentials = false;
        if (auth('client')->check()) {
            $hasLibraryEntries = \App\Models\LibraryEntry::where('cliente_id', auth('client')->id())->exists();
            $hasAssistants = \App\Models\Assistant::where('cliente_id', auth('client')->id())->exists();
            $hasCredentials = \App\Models\Credential::where('cliente_id', auth('client')->id())->exists();
        }

        $sidebarLink = static fn (bool $active): string => 'group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2.5 text-sm font-semibold transition ' . ($active
            ? 'border-blue-300 bg-white/10 text-white'
            : 'border-transparent text-white/75 hover:bg-white/10 hover:text-white');
        $sidebarIcon = static fn (bool $active): string => 'h-4 w-4 shrink-0 transition ' . ($active
            ? 'text-white'
            : 'text-white/50 group-hover:text-white/80');
        $submenuLink = static fn (bool $active): string => 'group flex items-center gap-3 rounded-lg px-4 py-2 text-sm font-semibold transition ' . ($active
            ? 'bg-white/10 text-white'
            : 'text-white/70 hover:bg-white/10 hover:text-white');
        $submenuIcon = static fn (bool $active): string => 'h-4 w-4 shrink-0 transition ' . ($active
            ? 'text-white'
            : 'text-white/45 group-hover:text-white/75');
    @endphp

    @if(auth('client')->check())
        <div
            x-data="{ clienteSidebarOpen: false, clienteToolsOpen: {{ $toolsActive ? 'true' : 'false' }}, clienteAccountOpen: false }"
            x-on:keydown.escape.window="clienteSidebarOpen = false; clienteToolsOpen = false; clienteAccountOpen = false"
            class="min-h-screen lg:flex"
        >
            <header class="sticky top-0 z-30 border-b border-white/10 px-4 py-3 text-white lg:hidden" style="background-color: {{ $menuBg }};">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Logo" class="h-9 w-9 shrink-0 rounded-lg bg-white/10 object-contain p-1">
                        @else
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10 text-sm font-semibold text-white">
                                {{ $clientInitial }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $clientName }}</p>
                            <p class="text-xs text-white/60">Painel do cliente</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-on:click="clienteSidebarOpen = true"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/15"
                        aria-label="Abrir menu"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </header>

            <div
                x-cloak
                x-show="clienteSidebarOpen"
                x-transition.opacity
                x-on:click="clienteSidebarOpen = false"
                class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden"
                aria-hidden="true"
            ></div>

            <aside
                x-cloak
                class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] flex-col text-white shadow-2xl transition-transform duration-200 lg:static lg:z-auto lg:w-64 lg:max-w-none lg:shadow-none"
                x-bind:class="clienteSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                style="background-color: {{ $menuBg }};"
            >
                <div class="border-b border-white/10 px-6 py-6">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="h-10 w-10 shrink-0 rounded-lg bg-white/10 object-contain p-1">
                            @else
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-sm font-semibold text-white">
                                    {{ $clientInitial }}
                                </span>
                            @endif
                            <div class="min-w-0">
                                <h1 class="truncate text-lg font-semibold tracking-wide">{{ $clientName }}</h1>
                                <p class="mt-1 text-xs text-white/55">Painel do cliente</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            x-on:click="clienteSidebarOpen = false"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/10 text-white transition hover:bg-white/15 lg:hidden"
                            aria-label="Fechar menu"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6 6 18"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5">
                    <a href="{{ route('cliente.dashboard') }}" x-on:click="clienteSidebarOpen = false" class="{{ $sidebarLink(request()->routeIs('cliente.dashboard')) }}">
                        <svg class="{{ $sidebarIcon(request()->routeIs('cliente.dashboard')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-5H10v5a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V10.5Z"/>
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('cliente.conexoes.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $sidebarLink(request()->routeIs('cliente.conexoes.*')) }}">
                        <svg class="{{ $sidebarIcon(request()->routeIs('cliente.conexoes.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 13a5 5 0 0 0 7.071 0l2.829-2.829a5 5 0 1 0-7.071-7.071L10 5M14 11a5 5 0 0 0-7.071 0L4.1 13.829a5 5 0 1 0 7.071 7.071L14 19"/>
                        </svg>
                        Conexões
                    </a>
                    <a href="{{ route('cliente.conversas.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $sidebarLink(request()->routeIs('cliente.conversas.*')) }}">
                        <svg class="{{ $sidebarIcon(request()->routeIs('cliente.conversas.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.5 11.5a8.5 8.5 0 0 1-12.22 7.63L3.5 20.5l1.37-4.78A8.5 8.5 0 1 1 20.5 11.5Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 10.5h7m-7 3h4.5"/>
                        </svg>
                        Conversas
                    </a>
                    <a href="{{ route('cliente.crm.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $sidebarLink(request()->routeIs('cliente.crm.*')) }}">
                        <svg class="{{ $sidebarIcon(request()->routeIs('cliente.crm.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5.5A1.5 1.5 0 0 1 5.5 4h3A1.5 1.5 0 0 1 10 5.5v13A1.5 1.5 0 0 1 8.5 20h-3A1.5 1.5 0 0 1 4 18.5v-13Zm10 0A1.5 1.5 0 0 1 15.5 4h3A1.5 1.5 0 0 1 20 5.5v7a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 14 12.5v-7Z"/>
                        </svg>
                        CRM
                    </a>
                    @if($hasCredentials)
                        <a href="{{ route('cliente.credentials.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $sidebarLink(request()->routeIs('cliente.credentials.*')) }}">
                            <svg class="{{ $sidebarIcon(request()->routeIs('cliente.credentials.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a4 4 0 1 1-7.5 2.5H3v5h3v3h3v-3h2.5A4 4 0 0 1 15 7Z"/>
                            </svg>
                            Credenciais
                        </a>
                    @endif
                    @if($hasAssistants)
                        <a href="{{ route('cliente.assistant.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $sidebarLink(request()->routeIs('cliente.assistant.*')) }}">
                            <svg class="{{ $sidebarIcon(request()->routeIs('cliente.assistant.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.5c1.657 0 3-1.343 3-3S13.657 1.5 12 1.5 9 2.843 9 4.5s1.343 3 3 3Zm-6 13.5a6 6 0 1 1 12 0H6Zm12-9 1.5 1.5L21 12m-3 0 1.5-1.5L21 12"/>
                            </svg>
                            Assistentes
                        </a>
                    @endif

                    <div class="relative" x-on:click.outside="clienteToolsOpen = false">
                        <button
                            type="button"
                            x-on:click="clienteToolsOpen = !clienteToolsOpen"
                            class="{{ $sidebarLink($toolsActive) }} w-full justify-between"
                            x-bind:aria-expanded="clienteToolsOpen.toString()"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="{{ $sidebarIcon($toolsActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/>
                                </svg>
                                Ferramentas
                            </span>
                            <svg class="h-3 w-3 shrink-0 text-white/50 transition" x-bind:class="clienteToolsOpen ? 'rotate-180 text-white/80' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4"/>
                            </svg>
                        </button>
                        <div
                            x-cloak
                            x-show="clienteToolsOpen"
                            x-transition.opacity.duration.150ms
                            class="mt-1 space-y-1 rounded-lg bg-black/10 p-2"
                        >
                            <a href="{{ route('cliente.mensagens-agendadas.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $submenuLink(request()->routeIs('cliente.mensagens-agendadas.*')) }}">
                                <svg class="{{ $submenuIcon(request()->routeIs('cliente.mensagens-agendadas.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm6 9v3l2 1"/>
                                </svg>
                                Mensagens agendadas
                            </a>
                            <a href="{{ route('cliente.campos-personalizados.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $submenuLink(request()->routeIs('cliente.campos-personalizados.*')) }}">
                                <svg class="{{ $submenuIcon(request()->routeIs('cliente.campos-personalizados.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5h12M6 12h12M6 19h8M4 3.5h16v17H4v-17Z"/>
                                </svg>
                                Campos personalizados
                            </a>
                            <a href="{{ route('cliente.webhook-links.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $submenuLink(request()->routeIs('cliente.webhook-links.*')) }}">
                                <svg class="{{ $submenuIcon(request()->routeIs('cliente.webhook-links.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 8a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm11 0a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7ZM10 12h4"/>
                                </svg>
                                Webhook links
                            </a>
                            @if($hasAssistants)
                                <a href="{{ route('cliente.sequences.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $submenuLink(request()->routeIs('cliente.sequences.*')) }}">
                                    <svg class="{{ $submenuIcon(request()->routeIs('cliente.sequences.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 5h4v4H6V5Zm8 10h4v4h-4v-4ZM10 7h4a4 4 0 0 1 4 4v4M8 9v2a4 4 0 0 0 4 4h2"/>
                                    </svg>
                                    Sequências
                                </a>
                                <a href="{{ route('cliente.images.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $submenuLink(request()->routeIs('cliente.images.*')) }}">
                                    <svg class="{{ $submenuIcon(request()->routeIs('cliente.images.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Zm3 11 4-4 3 3 2-2 3 3M8.5 9.5h.01"/>
                                    </svg>
                                    Mídias
                                </a>
                                <a href="{{ route('cliente.tags.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $submenuLink(request()->routeIs('cliente.tags.*')) }}">
                                    <svg class="{{ $submenuIcon(request()->routeIs('cliente.tags.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13 13 20 4 11V4h7l9 9Z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 8h.01"/>
                                    </svg>
                                    Tags
                                </a>
                            @endif
                        </div>
                    </div>

                    @if($hasLibraryEntries)
                        <a href="{{ route('cliente.library.index') }}" x-on:click="clienteSidebarOpen = false" class="{{ $sidebarLink(request()->routeIs('cliente.library.*')) }}">
                            <svg class="{{ $sidebarIcon(request()->routeIs('cliente.library.*')) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 4.5A2.5 2.5 0 0 1 7.5 2H20v18H7.5A2.5 2.5 0 0 0 5 22V4.5Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 18a2.5 2.5 0 0 1 2.5-2.5H20"/>
                            </svg>
                            Library
                        </a>
                    @endif
                </nav>

                <div class="mt-auto border-t border-white/10 px-4 py-4">
                    <div class="relative" x-on:click.outside="clienteAccountOpen = false">
                        <button
                            type="button"
                            x-on:click="clienteAccountOpen = !clienteAccountOpen"
                            class="group relative flex w-full items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 pr-10 text-left text-sm font-semibold text-white/90 shadow-sm shadow-black/20 transition hover:bg-white/10"
                            x-bind:aria-expanded="clienteAccountOpen.toString()"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-white ring-1 ring-white/20">
                                    {{ $clientInitial }}
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $clientName }}</span>
                                    <span class="block text-xs font-medium text-white/50">Conta</span>
                                </span>
                            </span>
                            <svg class="absolute right-3 top-3.5 h-3 w-3 text-white/45 transition" x-bind:class="clienteAccountOpen ? 'rotate-180 text-white/75' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4"/>
                            </svg>
                        </button>
                        <div
                            x-cloak
                            x-show="clienteAccountOpen"
                            x-transition.opacity.duration.150ms
                            class="absolute bottom-full left-0 right-0 z-10 mb-2 space-y-1 rounded-lg border border-white/10 bg-slate-950/95 p-2 shadow-lg backdrop-blur"
                        >
                            <a href="{{ route('cliente.password.edit') }}" x-on:click="clienteSidebarOpen = false" class="{{ $submenuLink($accountActive) }}">
                                <svg class="{{ $submenuIcon($accountActive) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11V8a5 5 0 0 1 10 0v3m-11 0h12v10H6V11Z"/>
                                </svg>
                                Senha
                            </a>
                            <form method="POST" action="{{ route('cliente.logout') }}">
                                @csrf
                                <button type="submit" class="group flex w-full items-center gap-3 rounded-lg px-4 py-2 text-left text-sm font-semibold text-rose-100 transition hover:bg-rose-500/20">
                                    <svg class="h-4 w-4 shrink-0 text-rose-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17l5-5-5-5M20 12H9m3 8H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h6"/>
                                    </svg>
                                    Sair
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                <div class="mx-auto max-w-6xl">
                    @hasSection('header')
                        <header class="mb-6 rounded-2xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
                            @yield('header')
                        </header>
                    @endif

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
    @else
        <div class="min-h-screen">
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
    @endif
    @stack('scripts')
</body>
</html>
