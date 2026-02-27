@extends('layouts.agencia')

@section('content')
    @php
        $dashboardUser = auth()->user();
        $clientesCount = \App\Models\Cliente::where('user_id', $dashboardUser?->id)->count();
        $conexoesCount = \App\Models\Conexao::whereHas('cliente', fn ($q) => $q->where('user_id', $dashboardUser?->id))->count();
        $settings = \App\Models\AgencySetting::where('user_id', $dashboardUser?->id)->first();
        $customDomain = $settings?->custom_domain;
        $clienteLoginUrl = $customDomain ? "https://{$customDomain}/cliente/login" : null;
        $userPlan = $dashboardUser?->plan;
        $planStorageGb = $userPlan?->storage_limit_mb ? ($userPlan->storage_limit_mb / 1024) : null;
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
                <h2 class="text-lg font-semibold text-slate-900">Detalhes do plano</h2>
                @if ($userPlan)
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Plano ativo</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Sem plano</span>
                @endif
            </div>

            @if ($userPlan)
                <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">ID do plano</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $userPlan->id }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Nome</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $userPlan->name }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Valor mensal</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">R$ {{ number_format((float) $userPlan->price_cents, 2, ',', '.') }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Máximo de conexões</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $userPlan->max_conexoes }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Armazenamento</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $userPlan->storage_limit_mb }} MB</p>
                        <p class="text-xs text-slate-500">{{ $planStorageGb !== null ? number_format($planStorageGb, 2, ',', '.') . ' GB' : '-' }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Última atualização</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $userPlan->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
                    </article>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">
                    Seu usuário ainda não possui plano vinculado. Fale com o administrador para configurar.
                </p>
            @endif
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
