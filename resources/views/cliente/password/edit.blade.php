@extends('layouts.cliente')

@section('title', 'Alterar Senha')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Alterar senha</h1>
            <p class="mt-1 text-sm text-slate-500">Use sua senha atual para definir uma nova senha de acesso ao painel do cliente.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('cliente.password.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="current_password">Senha atual</label>
                    <input
                        id="current_password"
                        name="current_password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @error('current_password')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="password">Nova senha</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="password_confirmation">Confirmar nova senha</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        minlength="6"
                        autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        Salvar
                    </button>
                    <span class="text-sm text-slate-500">A nova senha precisa ter pelo menos 6 caracteres.</span>
                </div>
            </form>
        </div>
    </div>
@endsection
