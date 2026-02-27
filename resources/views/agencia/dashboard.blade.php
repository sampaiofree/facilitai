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
        $asaasWebhooks = collect($dashboardUser?->asaasWebhooks)->sortByDesc('created_at')->values();
        $lastAsaasWebhook = $asaasWebhooks->first();
        $hasAsaasSubscription = !empty($dashboardUser?->asaas_sub);
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
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Link de pagamento do plano</p>
                        @if($hasAsaasSubscription)
                            <button
                                type="button"
                                id="open-plan-payment-link"
                                data-link-endpoint="{{ route('agencia.dashboard.asaas-subscription-link') }}"
                                class="mt-2 inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100"
                            >
                                Abrir link de pagamento
                            </button>
                        @else
                            <p class="mt-1 text-sm text-slate-500">Assinatura Asaas não encontrada.</p>
                        @endif
                    </article>
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Último pagamento</p>
                        @if($lastAsaasWebhook)
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $lastAsaasWebhook->event_type ?? '-' }}</p>
                            <p class="text-xs text-slate-600">{{ $lastAsaasWebhook->status ?? '-' }} · R$ {{ number_format((float) ($lastAsaasWebhook->value ?? 0), 2, ',', '.') }}</p>
                            <p class="text-xs text-slate-500">{{ $lastAsaasWebhook->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        @else
                            <p class="mt-1 text-sm text-slate-500">Sem registros de pagamento.</p>
                        @endif
                    </article>
                </div>

                <div class="mt-4">
                    <button
                        type="button"
                        id="toggle-plan-payments"
                        class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Ver pagamentos
                    </button>
                </div>

                <div id="plan-payments-list" class="mt-4 hidden overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2 text-left">ID</th>
                                <th class="px-3 py-2 text-left">Evento</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-left">Valor</th>
                                <th class="px-3 py-2 text-left">Pagamento</th>
                                <th class="px-3 py-2 text-left">Criado em</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($asaasWebhooks as $hook)
                                <tr>
                                    <td class="px-3 py-2">{{ $hook->id }}</td>
                                    <td class="px-3 py-2">{{ $hook->event_type ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $hook->status ?? '-' }}</td>
                                    <td class="px-3 py-2">R$ {{ number_format((float) ($hook->value ?? 0), 2, ',', '.') }}</td>
                                    <td class="px-3 py-2">{{ $hook->payment_id ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $hook->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-3 text-center text-slate-400">Sem registros de webhook Asaas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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

@push('scripts')
    <script>
        (() => {
            const openPaymentLinkButton = document.getElementById('open-plan-payment-link');
            const togglePaymentsButton = document.getElementById('toggle-plan-payments');
            const paymentsList = document.getElementById('plan-payments-list');

            openPaymentLinkButton?.addEventListener('click', async (event) => {
                event.preventDefault();
                const endpoint = openPaymentLinkButton.dataset?.linkEndpoint;
                if (!endpoint) return;

                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!response.ok || (data && data.error)) {
                    alert(data?.message || 'Falha ao obter link de cobrança.');
                    return;
                }

                if (data?.url) {
                    window.open(data.url, '_blank');
                }
            });

            togglePaymentsButton?.addEventListener('click', () => {
                if (!paymentsList) return;
                const isHidden = paymentsList.classList.contains('hidden');
                paymentsList.classList.toggle('hidden', !isHidden);
                togglePaymentsButton.textContent = isHidden ? 'Ocultar pagamentos' : 'Ver pagamentos';
            });
        })();
    </script>
@endpush
