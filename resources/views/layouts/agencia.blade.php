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
            </nav>
            <div class="mt-auto px-6 py-6 border-t border-slate-800 text-xs text-slate-400">
                {{ auth()->user()->name ?? 'Usuário' }}
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
