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
        } elseif (auth()->check()) {
            $agencySettings = AgencySetting::where('user_id', auth()->id())->first();
        }
        $faviconUrl = $agencySettings?->favicon_path ? Storage::disk('public')->url($agencySettings->favicon_path) : asset('favicon.ico');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <title>{{ config('app.name', 'Laravel') }} - Agência</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-800">
    @php
        $logoUrl = $agencySettings?->logo_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($agencySettings->logo_path)
            : null;
        $sidebarBg = $agencySettings?->primary_color ?: '#0f172a';
    @endphp
    <div class="min-h-screen flex">
        <aside class="w-64 shrink-0 text-slate-100 flex flex-col" style="background-color: {{ $sidebarBg }};">
            <div class="px-6 py-6 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="h-10 w-10 rounded-lg bg-slate-800/60 object-contain p-1">
                    @endif
                    <div>
                        <h1 class="text-lg font-semibold tracking-wide">Agência</h1>
                        <p class="text-xs text-slate-400 mt-1">Painel interno</p>
                    </div>
                </div>
            </div>
            @php
                $toolsActive = request()->routeIs('agencia.sequences.*') ||
                    request()->routeIs('agencia.images.*') ||
                    request()->routeIs('agencia.tags.*') ||
                    request()->routeIs('agencia.library.*');
                $profileActive = request()->routeIs('agencia.profile.*');
            @endphp
            <nav class="px-4 py-6 space-y-1">
                <a href="{{ route('agencia.dashboard') }}" class="group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.dashboard') ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('agencia.dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-5H10v5a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-10.5Z"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('agencia.clientes.index') }}" class="group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.clientes.*') ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('agencia.clientes.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20a4 4 0 0 0-8 0m12 0a6 6 0 0 0-4-5.659M3 20a6 6 0 0 1 4-5.659M15 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 2a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
                    </svg>
                    Clientes
                </a>
                <a href="{{ route('agencia.credentials.index') }}" class="group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.credentials.*') ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('agencia.credentials.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a4 4 0 1 1-7.5 2.5H3v5h3v3h3v-3h2.5A4 4 0 0 1 15 7Z"/>
                    </svg>
                    Credenciais
                </a>
                <a href="{{ route('agencia.conexoes.index') }}" class="group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.conexoes.*') ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('agencia.conexoes.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 13a5 5 0 0 0 7.071 0l2.829-2.829a5 5 0 1 0-7.071-7.071L10 5M14 11a5 5 0 0 0-7.071 0L4.1 13.829a5 5 0 1 0 7.071 7.071L14 19"/>
                    </svg>
                    Conexões
                </a>
                <a href="{{ route('agencia.assistant.index') }}" class="group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.assistant.*') ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('agencia.assistant.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.5c1.657 0 3-1.343 3-3S13.657 1.5 12 1.5 9 2.843 9 4.5s1.343 3 3 3Zm-6 13.5a6 6 0 1 1 12 0H6Zm12-9 1.5 1.5L21 12m-3 0 1.5-1.5L21 12"/>
                    </svg>
                    Assistentes
                </a>
                <div class="relative">
                    <button type="button" data-tools-dropdown-button class="group flex w-full items-center justify-between rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ $toolsActive ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <span class="flex items-center gap-3">
                            <svg class="h-4 w-4 shrink-0 {{ $toolsActive ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h7"/>
                            </svg>
                            Ferramentas
                        </span>
                        <svg class="h-3 w-3 shrink-0 text-slate-400 transition group-hover:text-slate-200" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4"/>
                        </svg>
                    </button>
                    <div data-tools-dropdown-menu class="absolute left-0 right-0 z-10 mt-1 hidden space-y-1 rounded-lg border border-slate-800 bg-slate-900/90 p-2 shadow-lg backdrop-blur">
                        <a href="{{ route('agencia.sequences.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.sequences.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Sequências</a>
                        <a href="{{ route('agencia.images.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.images.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Imagens</a>
                        <a href="{{ route('agencia.tags.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.tags.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}" >Tags</a>
                        <a href="{{ route('agencia.library.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.library.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Library</a>
                    </div>
                </div>
                <a href="{{ route('agencia.conversas.index') }}" class="group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.conversas.*') ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('agencia.conversas.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8M8 14h5m7-2a9 9 0 1 1-3.06-6.77L21 6v6h-6l2.2-2.2A7 7 0 1 0 19 12Z"/>
                    </svg>
                    Conversas
                </a>
                <a href="{{ route('agencia.agency-settings.edit') }}" class="group flex items-center gap-3 rounded-lg border-l-2 px-4 py-2 text-sm font-semibold transition {{ request()->routeIs('agencia.agency-settings.*') ? 'border-blue-500 bg-slate-800 text-white' : 'border-transparent text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="h-4 w-4 shrink-0 {{ request()->routeIs('agencia.agency-settings.*') ? 'text-white' : 'text-slate-400 group-hover:text-slate-200' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 2.5h3l.6 2.4a7.7 7.7 0 0 1 1.7.7l2.2-1.2 2.1 2.1-1.2 2.2c.3.5.5 1.1.7 1.7l2.4.6v3l-2.4.6a7.7 7.7 0 0 1-.7 1.7l1.2 2.2-2.1 2.1-2.2-1.2c-.5.3-1.1.5-1.7.7l-.6 2.4h-3l-.6-2.4a7.7 7.7 0 0 1-1.7-.7l-2.2 1.2-2.1-2.1 1.2-2.2a7.7 7.7 0 0 1-.7-1.7L2.5 13v-3l2.4-.6c.2-.6.4-1.2.7-1.7L4.4 5.5 6.5 3.4l2.2 1.2c.5-.3 1.1-.5 1.7-.7l.6-2.4Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9a3 3 0 1 1 0 6 3 3 0 0 1 0-6Z"/>
                    </svg>
                    Configurações
                </a>
            </nav>
            @php
                $sidebarUser = auth()->user();
                $sidebarPlan = $sidebarUser?->plan;
                $connectionsUsed = $sidebarUser?->conexoesCount() ?? 0;
                $connectionsLimit = $sidebarPlan?->max_conexoes;
                $storageUsed = $sidebarUser?->storage_used_mb ?? 0;
                $storageLimit = $sidebarPlan?->storage_limit_mb;
            @endphp
            <div class="px-6 py-4 border-t border-slate-800 text-xs text-slate-300 space-y-2">
                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Plano</div>
                <div class="text-sm font-semibold text-slate-100">{{ $sidebarPlan?->name ?? 'Sem plano' }}</div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>Conexões</span>
                    <span>{{ $connectionsUsed }} / {{ $connectionsLimit ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>Armazenamento</span>
                    <span>{{ $storageUsed }} MB / {{ $storageLimit ? $storageLimit . ' MB' : '-' }}</span>
                </div>
            </div>
            <div class="mt-auto px-6 py-4 border-t border-white/10">
                <div class="relative">
                    <button type="button" data-user-dropdown-button class="group relative flex w-full items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-2 pr-10 text-left text-sm font-semibold shadow-sm shadow-black/20 transition {{ $profileActive ? 'text-white' : 'text-slate-200 hover:bg-white/10' }}">
                        <span class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-800/70 text-slate-100 ring-1 ring-white/20 ring-offset-2 ring-offset-slate-900/70">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 13a6 6 0 0 0-12 0"/>
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm">{{ $sidebarUser?->name ?? 'Usuário' }}</span>
                                <span class="block truncate text-xs text-slate-400">{{ $sidebarUser?->email ?? '' }}</span>
                            </span>
                        </span>
                        <svg class="absolute right-3 top-2.5 h-3 w-3 text-slate-400 transition group-hover:text-slate-200" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 8l4 4 4-4"/>
                        </svg>
                    </button>
                    <div data-user-dropdown-menu class="absolute bottom-full left-0 right-0 z-10 mb-2 hidden space-y-1 rounded-lg border border-slate-800 bg-slate-900/95 p-2 shadow-lg backdrop-blur">
                        <a href="{{ route('agencia.profile.edit') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold transition {{ $profileActive ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Perfil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-lg px-4 py-2 text-left text-sm font-semibold text-rose-200 hover:bg-rose-500/20">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 px-10 py-8">
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
        </main>
    </div>
    <script>
        (() => {
            const bindDropdown = (buttonSelector, menuSelector) => {
                const button = document.querySelector(buttonSelector);
                const menu = document.querySelector(menuSelector);
                if (!button || !menu) {
                    return;
                }
                button.addEventListener('click', (event) => {
                    event.stopPropagation();
                    menu.classList.toggle('hidden');
                });
                menu.addEventListener('click', (event) => {
                    event.stopPropagation();
                });
                document.addEventListener('click', () => {
                    menu.classList.add('hidden');
                });
            };

            bindDropdown('[data-tools-dropdown-button]', '[data-tools-dropdown-menu]');
            bindDropdown('[data-user-dropdown-button]', '[data-user-dropdown-menu]');
        })();
    </script>
    @stack('scripts')
</body>
</html>
