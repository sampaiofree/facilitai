<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Agência</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-800">
    <div class="min-h-screen flex">
        <aside class="w-64 shrink-0 bg-slate-900 text-slate-100 flex flex-col">
            <div class="px-6 py-6 border-b border-slate-800">
                <h1 class="text-lg font-semibold tracking-wide">Agência</h1>
                <p class="text-xs text-slate-400 mt-1">Painel interno</p>
            </div>
            <nav class="px-4 py-6 space-y-2">
                <a href="{{ route('agencia.clientes.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.clientes.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Clientes</a>
                <a href="{{ route('agencia.credentials.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.credentials.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Credenciais</a>
                <a href="{{ route('agencia.conexoes.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.conexoes.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Conexões</a>
                <a href="{{ route('agencia.assistant.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.assistant.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Assistentes</a>
                <a href="{{ route('agencia.sequences.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.sequences.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Sequências</a>
                <a href="{{ route('agencia.conversas.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.conversas.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Conversas</a>
                <a href="{{ route('agencia.tags.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.tags.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Tags</a>
                <a href="{{ route('agencia.images.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.images.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Imagens</a>
                <a href="{{ route('agencia.library.index') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.library.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Library</a>
                <a href="{{ route('agencia.agency-settings.edit') }}" class="block rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs('agencia.agency-settings.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">Configurações</a>
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
            <div class="mt-auto px-6 py-6 border-t border-slate-800 text-xs text-slate-400">
                {{ auth()->user()->name ?? 'Usuário' }}
            </div>
            <div class="px-6 py-6 border-t border-slate-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left rounded-lg px-3 py-2 text-sm font-semibold text-slate-200 bg-slate-800 hover:bg-slate-700">Sair</button>
                </form>
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
    @stack('scripts')
</body>
</html>
