<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FacilitAI | Planos Inteligentes</title>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <script>
        window.pricingPage = function () {
            return {
                features: @json($features),
                monthlyPlans: @json($monthlyPlans),
                yearlyPlans: @json($yearlyPlans),
                faqs: @json($faqs),
                openFaq: null,
                toggleFaq(idx) {
                    this.openFaq = this.openFaq === idx ? null : idx;
                },
                refreshIcons() {
                    if (window.lucide && typeof window.lucide.createIcons === 'function') {
                        lucide.createIcons();
                    }
                },
            };
        };
    </script>
    <div
        x-data="pricingPage"
        x-init="$nextTick(() => refreshIcons())"
        x-effect="refreshIcons()"
        class="min-h-screen relative"
    >
        <header class="fixed inset-x-0 top-0 z-50 bg-white/90 backdrop-blur-md border-b border-white/40 shadow-sm">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col gap-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <img src="/homepage/novalogo.svg" alt="FacilitAI" class="h-10 w-auto" />
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-[0.3em]">FacilitAI</p>
                        <p class="text-lg font-semibold text-gray-900 leading-tight">Atendimento inteligente</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3 sm:gap-4">
                    <a
                        href="#features"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 transition"
                    >
                        Recursos
                    </a>
                    <a
                        href="#plans"
                        class="px-5 py-2 rounded-full bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-semibold shadow-lg shadow-purple-500/30 transition-transform hover:-translate-y-0.5"
                    >
                        Ver Planos
                    </a>
                </div>
            </div>
        </header>

        <main class="pt-28">
            <section class="relative overflow-hidden isolate">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-900 via-purple-700 to-indigo-600 opacity-80"></div>
                <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 flex flex-col gap-10 text-white">
                    <div class="max-w-3xl space-y-6">
                        <p class="text-sm uppercase tracking-[0.5em] text-purple-200">WhatsApp + IA</p>
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight">
                            Atendimento inteligente no WhatsApp que funciona <span class="text-yellow-200">24/7</span>
                        </h1>
                        <p class="text-lg text-purple-100">
                            Automatize conversas, agende compromissos e atenda seus clientes com IA de ponta. Tudo funcionando via navegador, sem instalação.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <a
                                href="#plans"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white text-purple-700 font-semibold shadow-lg shadow-black/30 hover:bg-gray-100 transition"
                            >
                                Começar Agora
                            </a>
                            <a
                                href="#faq"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-full border border-white/60 text-white font-semibold hover:border-white"
                            >
                                Conhecer FAQ
                            </a>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-2 bg-white/5 rounded-2xl p-5 shadow-xl backdrop-blur">
                            <p class="text-sm text-purple-200 uppercase">Conectividade</p>
                            <p class="text-2xl font-bold">Todos os planos</p>
                            <p class="text-sm text-purple-100">Mensagens ilimitadas, notificações e agendamentos automáticos.</p>
                        </div>
                        <div class="flex flex-col gap-2 bg-white/5 rounded-2xl p-5 shadow-xl backdrop-blur">
                            <p class="text-sm text-purple-200 uppercase">Segurança</p>
                            <p class="text-2xl font-bold">SSL + Backup</p>
                            <p class="text-sm text-purple-100">Proteção total para dados e histórico de conversas.</p>
                        </div>
                        <div class="flex flex-col gap-2 bg-white/5 rounded-2xl p-5 shadow-xl backdrop-blur">
                            <p class="text-sm text-purple-200 uppercase">Suporte</p>
                            <p class="text-2xl font-bold">Humanizado</p>
                            <p class="text-sm text-purple-100">Time dedicado pronto para ajudar em minutos.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
                <div class="max-w-6xl mx-auto space-y-10">
                    <div class="text-center space-y-3">
                        <p class="text-sm uppercase tracking-[0.3em] text-purple-500">Recursos integrados</p>
                        <h2 class="text-3xl font-bold text-gray-900">Tudo que você precisa em qualquer plano</h2>
                        <p class="text-lg text-gray-600">Todos os recursos inclusos. Sem surpresas e sem custos escondidos.</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="(feature, idx) in features" :key="idx">
                            <div class="flex items-start gap-3 p-4 border border-gray-100 rounded-2xl">
                                <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                    <i :data-lucide="feature.icon" class="w-6 h-6"></i>
                                </div>
                                <p class="text-gray-700 font-medium leading-tight" x-text="feature.text"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <section id="plans" class="py-20 px-4 sm:px-6 lg:px-8">
                <div class="max-w-6xl mx-auto space-y-16">
                    <div class="text-center space-y-3">
                        <p class="text-sm uppercase tracking-[0.4em] text-purple-500">Planos</p>
                        <h2 class="text-4xl font-bold text-gray-900">Escolha o seu ritmo</h2>
                        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                            Conexões, assistentes e automações ilimitadas. Você precisa apenas decidir quantos ambientes quer ativar.
                        </p>
                    </div>

                    <div class="space-y-10">
                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base uppercase tracking-[0.4em] text-purple-500">Planos Mensais</p>
                                    <h3 class="text-2xl font-semibold text-gray-900">Pagamento mensal sem fidelidade</h3>
                                </div>
                                <span class="px-4 py-2 rounded-full bg-purple-50 text-purple-700 text-sm font-semibold">Pagamento seguro</span>
                            </div>
                            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                                <template x-for="(plan, idx) in monthlyPlans" :key="'monthly-'+idx">
                                    <div class="flex flex-col h-full border border-gray-200 rounded-3xl p-6 gap-4 hover:shadow-2xl transition">
                                        <div class="space-y-1 text-center">
                                            <p class="text-sm uppercase tracking-[0.4em] text-gray-400">
                                                <span x-text="plan.connections"></span>
                                                <span x-text="plan.connections === 1 ? ' conexão' : ' conexões'"></span>
                                            </p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                <span>R$</span><span x-text="plan.price_label"></span><span class="text-sm text-gray-500">/mês</span>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <span x-text="plan.assistants"></span>
                                                <span x-text="plan.assistants === 1 ? ' assistente' : ' assistentes'"></span>
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs text-purple-600 font-semibold" x-text="`Valor por conexão: R$${plan.price_per_connection_label}`"></p>
                                        </div>
                                        <a
                                            :href="plan.checkout"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-auto inline-flex items-center justify-center rounded-xl bg-purple-600 text-white font-semibold py-3 px-4 text-sm shadow-lg shadow-purple-500/30 hover:brightness-110 transition"
                                        >
                                            Checkout
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-base uppercase tracking-[0.4em] text-purple-500">Planos Anuais</p>
                                    <h3 class="text-2xl font-semibold text-gray-900">Tarifa reduzida com pagamento mensal</h3>
                                </div>
                                <span class="px-4 py-2 rounded-full bg-green-50 text-green-800 text-sm font-semibold">Economize 2 meses</span>
                            </div>
                            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                                <template x-for="(plan, idx) in yearlyPlans" :key="'yearly-'+idx">
                                    <div class="flex flex-col h-full border border-gray-200 rounded-3xl p-6 gap-4 hover:shadow-2xl transition">
                                        <div class="space-y-1 text-center">
                                            <p class="text-sm uppercase tracking-[0.4em] text-gray-400">
                                                <span x-text="plan.connections"></span>
                                                <span x-text="plan.connections === 1 ? ' conexão' : ' conexões'"></span>
                                            </p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                <span>R$</span><span x-text="plan.price_label"></span><span class="text-sm text-gray-500">/mês</span>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <span x-text="plan.assistants"></span>
                                                <span x-text="plan.assistants === 1 ? ' assistente' : ' assistentes'"></span>
                                            </p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs text-purple-600 font-semibold" x-text="`Valor por conexão: R$${plan.price_per_connection_label}`"></p>
                                            <p class="text-xs text-green-600 font-bold" x-show="plan.savings_label" x-text="plan.savings_label"></p>
                                        </div>
                                        <a
                                            :href="plan.checkout"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-auto inline-flex items-center justify-center rounded-xl bg-white text-purple-700 font-semibold py-3 px-4 text-sm border border-purple-100 shadow-sm hover:shadow-lg transition"
                                        >
                                            Checkout
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-purple-100 rounded-3xl p-8 text-center">
                        <p class="text-base font-semibold text-purple-700">Em todos os planos, você tem:</p>
                        <div class="mt-4 flex flex-wrap justify-center gap-5 text-sm text-gray-600 items-center">
                            <span class="flex items-center gap-2">
                                <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                Todas as ferramentas
                            </span>
                            <span class="flex items-center gap-2">
                                <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                Mensagens ilimitadas
                            </span>
                            <span class="flex items-center gap-2">
                                <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                Suporte humanizado
                            </span>
                            <span class="flex items-center gap-2">
                                <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                Garantia de 7 dias
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 bg-slate-100">
                <div class="max-w-3xl mx-auto space-y-8">
                    <div class="text-center space-y-3">
                        <p class="text-sm uppercase tracking-[0.4em] text-purple-500">FAQ</p>
                        <h2 class="text-3xl font-bold text-gray-900">Perguntas Frequentes</h2>
                        <p class="text-gray-600">
                            Respondemos as dúvidas que aparecem sempre, em menos de um minuto você tem clareza completa.
                        </p>
                    </div>
                    <div class="space-y-4">
                        <template x-for="(faq, idx) in faqs" :key="faq.q">
                            <article class="border border-gray-200 rounded-2xl overflow-hidden">
                                <button
                                    type="button"
                                    @click="toggleFaq(idx)"
                                    :aria-expanded="openFaq === idx ? 'true' : 'false'"
                                    class="w-full px-6 py-4 flex items-center justify-between text-left bg-white hover:bg-slate-50 transition"
                                >
                                    <div>
                                        <p class="text-lg font-semibold text-gray-900" x-text="faq.q"></p>
                                    </div>
                                    <i
                                        data-lucide="chevron-down"
                                        class="w-6 h-6 text-gray-400 transition-transform"
                                        :class="openFaq === idx ? 'rotate-180 text-purple-600' : ''"
                                    ></i>
                                </button>
                                <div
                                    class="px-6 transition-all duration-300 overflow-hidden"
                                    :class="openFaq === idx ? 'max-h-96 pb-4' : 'max-h-0 pb-0'"
                                >
                                    <p class="text-gray-700 py-2" x-text="faq.a"></p>
                                </div>
                            </article>
                        </template>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-gray-900 text-white py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto text-center">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <img src="/homepage/novalogoDark.svg" alt="FacilitAI" class="h-10 w-auto" />
                    <span class="text-2xl font-semibold">FacilitAI</span>
                </div>
                <p class="text-gray-400 mb-6">
                    Atendimento inteligente no WhatsApp que funciona 24/7.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-6 text-gray-400 text-sm">
                    <span>Pagamento seguro</span>
                    <span>SSL certificado</span>
                    <span>Suporte premium</span>
                </div>
                <p class="text-gray-500 text-xs mt-8">
                    © 2024 FacilitAI. Todos os direitos reservados.
                </p>
            </div>
        </footer>

    </div>
</body>
</html>
