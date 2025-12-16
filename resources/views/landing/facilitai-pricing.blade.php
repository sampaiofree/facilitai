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
                <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 space-y-10 text-white">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                        <div class="max-w-3xl space-y-6">
                            <p class="text-sm uppercase tracking-[0.5em] text-purple-200">WhatsApp + IA</p>
                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight">
                                Atenda, responda e venda no WhatsApp automaticamente
                            </h1>
                            <p class="text-lg text-purple-100">
                                Assistentes de IA prontos para empresas e agências, funcionando 24 horas por dia sem você precisar ficar no celular.
                            </p>
                            <div class="space-y-1 text-base text-purple-100">
                                <p>Ideal para empresas que atendem clientes pelo WhatsApp.</p>
                                <p>E para agências que querem vender automação para seus clientes.</p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a
                                    href="#plans"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white text-purple-700 font-semibold shadow-lg shadow-black/30 hover:bg-gray-100 transition"
                                >
                                    Ver planos e começar
                                </a>
                                <a
                                    href="#faq"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-full border border-white/60 text-white font-semibold hover:border-white"
                                >
                                    Conhecer FAQ
                                </a>
                            </div>
                        </div>
                        <div>
                            <div class="aspect-video rounded-3xl overflow-hidden border border-white/20 shadow-2xl shadow-black/30">
                                <iframe
                                    src="https://www.youtube.com/embed/alsj-l6fL80?rel=0"
                                    title="Apresentação FacilitAI"
                                    class="w-full h-full"
                                    loading="lazy"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            </div>
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
                            <p class="text-2xl font-bold">Segurança e privacidade</p>
                            <p class="text-sm text-purple-100">As conversas acontecem direto no WhatsApp. O FacilitAI não armazena histórico de mensagens.</p>
                        </div>
                        <div class="flex flex-col gap-2 bg-white/5 rounded-2xl p-5 shadow-xl backdrop-blur">
                            <p class="text-sm text-purple-200 uppercase">Suporte</p>
                            <p class="text-2xl font-bold">Humanizado</p>
                            <p class="text-sm text-purple-100">Time dedicado pronto para ajudar em minutos.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div class="space-y-8">
                        <div class="space-y-4">
                            <p class="text-sm uppercase tracking-[0.3em] text-purple-500">O que é o FacilitAI</p>
                            <h2 class="text-3xl font-bold text-gray-900">Assistentes de IA prontos para WhatsApp</h2>
                            <p class="text-lg text-gray-600">
                                O FacilitAI é uma plataforma que cria assistentes de IA para WhatsApp.
                            </p>
                            <p class="text-lg text-gray-600">
                                Eles respondem clientes, tiram dúvidas, fazem agendamentos e follow-up sem você precisar ficar no celular.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex items-center gap-3 rounded-2xl border border-gray-100 p-4 bg-slate-50">
                                <i data-lucide="check" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Funciona direto no WhatsApp</p>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-gray-100 p-4 bg-slate-50">
                                <i data-lucide="check" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Sem código</p>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-gray-100 p-4 bg-slate-50">
                                <i data-lucide="check" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Você controla tudo</p>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-gray-100 p-4 bg-slate-50">
                                <i data-lucide="check" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Use para vender como serviço</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <p class="text-base font-semibold text-gray-800">Na prática, funciona assim 👇</p>
                        <div class="rounded-3xl overflow-hidden border border-gray-100 shadow-xl bg-black w-full max-w-sm mx-auto lg:ml-auto" style="aspect-ratio: 9 / 16;">
                            <video
                                src="https://app.3f7.org/storage/homepage/demontracao_conversa-6.mp4"
                                autoplay
                                muted
                                loop
                                playsinline
                                preload="auto"
                                class="w-full h-full object-cover"
                            ></video>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-gradient-to-b from-white to-slate-100 py-20 px-4 sm:px-6 lg:px-8">
                <div class="max-w-6xl mx-auto text-center">
                    <div class="space-y-4">
                        <p class="text-sm uppercase tracking-[0.3em] text-purple-500">Como é fácil</p>
                        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">
                            Crie seu assistente em apenas <span class="text-purple-600">2 passos simples</span>
                        </h2>
                        <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                            Você não precisa saber programar. Em poucos minutos seu assistente já está ativo e respondendo no WhatsApp.
                        </p>
                    </div>

                    <div class="mt-12 space-y-16">
                        <div class="grid lg:grid-cols-2 gap-10 items-center">
                            <div class="text-left space-y-4">
                                <h3 class="text-2xl font-semibold text-slate-900">1️⃣ Crie seu assistente</h3>
                                <ul class="text-slate-700 space-y-2">
                                    <li>• Escolha o nome e a personalidade do seu assistente.</li>
                                    <li>• Defina o tipo de atendimento (vendas, suporte, agendamento, etc.).</li>
                                    <li>• Clique em <strong>"Criar"</strong> e pronto — seu assistente já está ativo!</li>
                                </ul>
                                <p class="text-emerald-600 font-medium">💡 Leva menos de 1 minuto para criar o primeiro.</p>
                            </div>
                            <div class="rounded-2xl overflow-hidden shadow-xl border border-slate-200 bg-black">
                                <video
                                    class="w-full h-auto"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                    preload="auto"
                                >
                                    <source src="{{ asset('storage/homepage/demontracao_criando_assistente.mp4') }}" type="video/mp4" />
                                </video>
                            </div>
                        </div>

                        <div class="grid lg:grid-cols-2 gap-10 items-center">
                            <div class="rounded-2xl overflow-hidden shadow-xl border border-slate-200 bg-black lg:order-1 order-2">
                                <video
                                    class="w-full h-auto"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                    preload="auto"
                                >
                                    <source src="{{ asset('storage/homepage/demontracao_conectando.mp4') }}" type="video/mp4" />
                                </video>
                            </div>
                            <div class="text-left space-y-4 lg:order-2 order-1">
                                <h3 class="text-2xl font-semibold text-slate-900">2️⃣ Conecte ao seu WhatsApp</h3>
                                <ul class="text-slate-700 space-y-2">
                                    <li>• Escaneie o QR Code com seu celular.</li>
                                    <li>• Em segundos, seu número estará conectado.</li>
                                    <li>• Seu assistente começa a responder automaticamente.</li>
                                </ul>
                                <p class="text-emerald-600 font-medium">⚡ Criou, conectou, começou a vender.</p>
                            </div>
                        </div>
                    </div>

                    <p class="text-lg text-slate-700 mt-16 font-medium">
                        Em menos de <span class="text-purple-600 font-bold">5 minutos</span>, seu WhatsApp se transforma em um atendente inteligente que trabalha por você 24h por dia.
                    </p>
                    <div class="mt-8">
                        <a
                            href="#plans"
                            class="inline-flex items-center justify-center bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-4 px-8 rounded-full shadow-lg hover:scale-105 transition-transform"
                        >
                            🚀 Criar meu assistente agora
                        </a>
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

            <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white to-purple-50">
                <div class="max-w-5xl mx-auto space-y-10">
                    <div class="bg-white border border-purple-100 rounded-3xl shadow-xl p-8 space-y-6">
                        <div class="space-y-2 text-center">
                            <p class="text-sm uppercase tracking-[0.3em] text-purple-500">Uso de IA sem limite</p>
                            <h2 class="text-3xl font-bold text-gray-900">IA sem limite de conversas</h2>
                            <p class="text-lg text-gray-600">
                                Aqui você não paga “pacote fechado de mensagens”.
                                Você usa o quanto precisar e paga a IA direto para a OpenAI, a preço de custo.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex items-center gap-3 rounded-2xl border border-purple-100 p-4 bg-purple-50/50">
                                <i data-lucide="check-circle" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Sem limite de contatos</p>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-purple-100 p-4 bg-purple-50/50">
                                <i data-lucide="check-circle" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Sem limite de conversas</p>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-purple-100 p-4 bg-purple-50/50">
                                <i data-lucide="check-circle" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Nada bloqueia quando “acaba mensagem”</p>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-purple-100 p-4 bg-purple-50/50">
                                <i data-lucide="check-circle" class="w-5 h-5 text-purple-600"></i>
                                <p class="font-medium text-gray-800">Você paga só pelo que usar</p>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-900 text-white p-6 space-y-2">
                            <p class="text-sm uppercase tracking-[0.3em] text-purple-200">Exemplo real</p>
                            <p class="text-lg">
                                Um uso médio consome cerca de <span class="font-semibold">3 milhões de tokens por mês</span>,
                                o que dá em média <span class="font-semibold">R$18 pagos direto à OpenAI</span>.
                            </p>
                            <p class="text-sm text-purple-200">Sem taxa, sem margem, sem intermediário.</p>
                        </div>
                        <div class="text-center space-y-2">
                            <p class="text-base text-gray-700">
                                Outras plataformas limitam mensagens ou cobram pacotes caros.
                                Aqui você tem liberdade total e custo baixo.
                            </p>
                            <p class="text-lg font-semibold text-gray-900">Mais controle. Mais transparência. Mais barato.</p>
                        </div>
                    </div>

                    <div class="bg-white border border-purple-100 rounded-3xl shadow-lg p-6 space-y-4">
                        <p class="text-sm uppercase tracking-[0.3em] text-purple-500 text-center">Micro FAQ</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-700">
                            <div class="space-y-1">
                                <p class="font-semibold text-gray-900">Os tokens estão inclusos no plano?</p>
                                <p>Não. Você paga direto à OpenAI, sem intermediários.</p>
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-gray-900">Posso gastar muito sem perceber?</p>
                                <p>Não. Você acompanha o consumo e pode controlar.</p>
                            </div>
                            <div class="space-y-1">
                                <p class="font-semibold text-gray-900">Tem limite de conversas?</p>
                                <p>Não. Conversas e contatos são ilimitados.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="plans" class="py-20 px-4 sm:px-6 lg:px-8">
                <div class="max-w-6xl mx-auto space-y-16">
                    <div class="text-center space-y-3">
                        <p class="text-sm uppercase tracking-[0.4em] text-purple-500">Planos</p>
                        <h2 class="text-4xl font-bold text-gray-900">Escolha o seu ritmo</h2>
                        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                            Todos os planos têm todas as ferramentas.
                        </p>
                        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                            O que muda é apenas a quantidade de conexões de WhatsApp.
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
                                            <p class="text-sm uppercase tracking-[0.2em] text-gray-400" x-text="plan.connections === 1 ? '1 WhatsApp ativo' : plan.connections + ' WhatsApps ativos'"></p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                <span>R$</span><span x-text="plan.price_label"></span><span class="text-sm text-gray-500">/mês</span>
                                            </p>
                                            <p class="text-sm text-gray-500" x-text="plan.assistants === 1 ? 'Com 1 atendente de IA' : 'Com ' + plan.assistants + ' atendentes de IA'"></p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs text-purple-600 font-semibold" x-text="`Valor por WhatsApp: R$${plan.price_per_connection_label}`"></p>
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
                                            <p class="text-sm uppercase tracking-[0.2em] text-gray-400" x-text="plan.connections === 1 ? '1 WhatsApp ativo' : plan.connections + ' WhatsApps ativos'"></p>
                                            <p class="text-3xl font-bold text-gray-900">
                                                <span>R$</span><span x-text="plan.price_label"></span><span class="text-sm text-gray-500">/mês</span>
                                            </p>
                                            <p class="text-sm text-gray-500" x-text="plan.assistants === 1 ? 'Com 1 atendente de IA' : 'Com ' + plan.assistants + ' atendentes de IA'"></p>
                                        </div>
                                        <div class="text-center">
                                            <p class="text-xs text-purple-600 font-semibold" x-text="`Valor por WhatsApp: R$${plan.price_per_connection_label}`"></p>
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

                    <div class="text-center rounded-3xl border border-purple-100 bg-purple-50 p-8">
                        <p class="text-lg font-semibold text-purple-800">Quanto mais conexões, mais barato fica cada WhatsApp.</p>
                        <p class="text-sm text-purple-700 mt-2">Ideal para quem atende vários clientes ou várias unidades.</p>
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

            <section class="py-16 px-4 sm:px-6 lg:px-8 bg-slate-900 text-white">
                <div class="max-w-4xl mx-auto text-center space-y-4">
                    <p class="text-sm uppercase tracking-[0.4em] text-purple-200">Para agências</p>
                    <h2 class="text-3xl font-bold">É agência? Isso é pra você.</h2>
                    <p class="text-lg text-purple-100">Use o FacilitAI para criar assistentes para seus clientes.</p>
                    <div class="space-y-2 text-lg text-purple-50">
                        <p>Cobre setup, mensalidade ou ambos.</p>
                        <p>A plataforma fica nos bastidores.</p>
                    </div>
                </div>
            </section>

            <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 bg-slate-100">
                <div class="max-w-3xl mx-auto space-y-8">
                    <div class="text-center space-y-3">
                        <p class="text-sm uppercase tracking-[0.4em] text-purple-500">FAQ</p>
                        <h2 class="text-3xl font-bold text-gray-900">Perguntas Frequentes</h2>
                        <p class="text-gray-600">Respostas rápidas e sem complicação.</p>
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

            <section class="py-16 px-4 sm:px-6 lg:px-8 bg-white">
                <div class="max-w-4xl mx-auto text-center space-y-6">
                    <h2 class="text-3xl font-bold text-gray-900">Comece agora e tenha um atendente de IA no WhatsApp hoje mesmo.</h2>
                    <a
                        href="#plans"
                        class="inline-flex items-center justify-center rounded-full bg-purple-600 px-8 py-3 text-lg font-semibold text-white shadow-lg shadow-purple-500/30 hover:brightness-110 transition"
                    >
                        Escolher plano
                    </a>
                </div>
            </section>
        </main>

        @include('homepage.footer')

    </div>
</body>
</html>
