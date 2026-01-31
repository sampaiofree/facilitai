<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cliente')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen">
        @if(auth('client')->check())
            <header class="bg-white border-b border-slate-200">
                <div class="mx-auto max-w-6xl px-6 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 text-sm font-semibold text-slate-800">
                        <a href="{{ route('cliente.dashboard') }}" class="{{ request()->routeIs('cliente.dashboard') ? 'text-blue-600' : 'hover:text-blue-600' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('cliente.conexoes.index') }}" class="{{ request()->routeIs('cliente.conexoes.*') ? 'text-blue-600' : 'hover:text-blue-600' }}">
                            Conexões
                        </a>
                        <a href="{{ route('cliente.library.index') }}" class="{{ request()->routeIs('cliente.library.*') ? 'text-blue-600' : 'hover:text-blue-600' }}">
                            Library
                        </a>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-700">
                        <span>{{ auth('client')->user()->nome ?? 'Cliente' }}</span>
                        <form method="POST" action="{{ route('cliente.logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg border border-slate-200 px-3 py-1 font-semibold text-slate-700 hover:bg-slate-50">
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
