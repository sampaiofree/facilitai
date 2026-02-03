@extends('layouts.agencia')

@section('content')
    @php
        $dashboardUser = auth()->user();
        $clientesCount = \App\Models\Cliente::where('user_id', $dashboardUser?->id)->count();
        $conexoesCount = \App\Models\Conexao::whereHas('cliente', fn ($q) => $q->where('user_id', $dashboardUser?->id))->count();
        $settings = \App\Models\AgencySetting::where('user_id', $dashboardUser?->id)->first();
        $customDomain = $settings?->custom_domain;
        $clienteLoginUrl = $customDomain ? "https://{$customDomain}/cliente/login" : null;
    @endphp
    <div class="space-y-6">
        <section class="bg-white shadow rounded-2xl p-6 border border-slate-100">
            <div class="flex flex-col gap-2">
                <p class="text-xs uppercase tracking-widest text-slate-400">Painel da Agência</p>
                <h1 class="text-3xl font-bold text-slate-900">
                    Bem-vindo, {{ auth()->user()->name ?? 'Agente' }}
                </h1>
                <p class="text-sm text-slate-500">
                    Acompanhe rapidamente o status das suas conexões, credenciais e campanhas.
                </p>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <article class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-sm text-slate-500">Clientes ativos</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ $clientesCount }}</p>
                </article>
                <article class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-sm text-slate-500">Conexões monitoradas</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ $conexoesCount }}</p>
                </article>
                <article class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-sm text-slate-500">Link de acesso do cliente</p>
                    @if ($clienteLoginUrl)
                        <p class="mt-1 text-sm font-semibold text-slate-900 break-all">{{ $clienteLoginUrl }}</p>
                        <p class="mt-2 text-xs text-slate-500">Esse é o link que seus clientes acessam para entrar.</p>
                    @else
                        <p class="text-sm text-slate-500">Defina um domínio customizado para liberar este link.</p>
                    @endif
                </article>
            </div>
        </section>

        <section class="bg-white shadow rounded-2xl p-6 border border-slate-100">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Próximos passos</h2>
                <span class="text-xs text-slate-400">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
            <ul class="mt-4 space-y-3 text-sm text-slate-600">
                <li>• Confira os clientes listados no menu lateral para garantir bot-enabled.</li>
                <li>• Use as sequências para manter a jornada do lead fluindo.</li>
            </ul>
        </section>
    </div>
@endsection
