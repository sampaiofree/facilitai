@extends('layouts.cliente')

@section('title', 'Login Cliente')

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-md">
            <h1 class="text-xl font-semibold mb-4">Area do Cliente</h1>

            <form method="POST" action="{{ route('cliente.login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">Senha</label>
                    <input id="password" name="password" type="password" required class="mt-1 w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <label for="remember" class="ml-2 text-sm text-slate-600">Lembrar-me</label>
                </div>

                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Entrar</button>
            </form>
        </div>
    </div>
@endsection
