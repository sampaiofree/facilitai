@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Meu perfil</h2>
            <p class="text-sm text-slate-500">Atualize seus dados pessoais e de contato.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form method="POST" action="{{ route('agencia.profile.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="name">Nome completo</label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="email">E-mail</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $user->email) }}"
                        readonly
                        class="mt-1 w-full cursor-not-allowed rounded-lg border-slate-200 bg-slate-50 text-slate-500 shadow-sm"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="cpf_cnpj">CPF/CNPJ</label>
                    <input
                        id="cpf_cnpj"
                        name="cpf_cnpj"
                        type="text"
                        value="{{ old('cpf_cnpj', $user->cpf_cnpj) }}"
                        required
                        x-data
                        x-mask="99999999999999"
                        placeholder="Somente números"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide" for="mobile_phone">Telefone celular</label>
                    <input
                        id="mobile_phone"
                        name="mobile_phone"
                        type="text"
                        value="{{ old('mobile_phone', $user->mobile_phone) }}"
                        required
                        x-data
                        x-mask="999999999999999"
                        placeholder="Ex: 62999999999"
                        class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Salvar
                </button>
                @if (session('status') === 'profile-updated')
                    <span class="text-sm text-emerald-600 font-medium">Alterações salvas com sucesso.</span>
                @endif
            </div>
        </form>
    </div>
@endsection
