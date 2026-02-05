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
        $hasLibraryEntries = false;
        $hasAssistants = false;
        if (auth('client')->check()) {
            $hasLibraryEntries = \App\Models\LibraryEntry::where('cliente_id', auth('client')->id())->exists();
            $hasAssistants = \App\Models\Assistant::where('cliente_id', auth('client')->id())->exists();
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
                        @if($hasAssistants)
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
                    <div class="flex items-center gap-3 text-sm text-white/80">
                        <span>{{ auth('client')->user()->nome ?? 'Cliente' }}</span>
                        <form method="POST" action="{{ route('cliente.logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-white/20 px-3 py-1 font-semibold text-white hover:bg-white/10">
                                Sair
                            </button>
                        </form>
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
