<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FacilitAI - Automatize seu Atendimento Jurídico com IA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
    [x-cloak] { display: none !important; }
    body {
        font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
    }
  </style>
</head>

<!--<body class="antialiased text-gray-800 bg-slate-50 selection:bg-emerald-300 selection:text-emerald-900">-->
<body class="antialiased text-gray-800 bg-slate-50 transition-all duration-300 opacity-0" x-data @load.window="document.body.classList.remove('opacity-0')">
<div id="preloader" 
     class="fixed inset-0 flex items-center justify-center bg-slate-50 z-[9999]" 
     x-data 
     x-init="window.addEventListener('load', () => $el.remove())">
  <div class="w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
</div>

    
    <!-- CORPO PRINCIPAL -->
    <main x-cloak>

        <!-- Bloco 1: Hero -->
        <section id="hero" class="relative overflow-hidden bg-gradient-to-br from-indigo-900 via-indigo-800 to-indigo-700 text-white py-20">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col lg:flex-row items-center justify-between gap-12 relative z-10">
                    
                    <!-- Conteúdo textual -->
                    <div class="w-full lg:w-1/2 space-y-6" x-data="{ enviado: false }" x-cloak>
                        <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">
                            Automatize o atendimento do seu <span class="text-emerald-400">escritório de advocacia</span> com Inteligência Artificial
                        </h1>

                        <p class="text-lg text-indigo-100">
                            Tenha um assistente virtual jurídico que responde dúvidas, coleta informações e agenda consultas diretamente no WhatsApp — 24 horas por dia.
                        </p>

                            <a href="" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/facilitai/assistentes-de-ia-para-whatsapp?hide_gdpr_banner=1'});return false;"
                            class="group inline-flex items-center gap-3 px-8 py-4 bg-emerald-500 hover:bg-emerald-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-emerald-500/50 transition-all transform hover:scale-105">
                            <span>Agende sua demonstração gratuita</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Mockup imagem -->
                    <div class="w-full lg:w-1/2 flex justify-center">
                        <img src="{{ asset('storage/lp_adv/e.webp') }}" alt="Assistente jurídico no WhatsApp"
                            class="w-80 md:w-[420px] drop-shadow-2xl rounded-3xl">
                    </div>
                </div>

                <!-- Efeito de fundo -->
                <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/50 via-transparent to-transparent pointer-events-none z-0"></div>

        </section>

        <!-- Bloco 2: Benefícios / Problemas resolvidos -->
        <section id="beneficios" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
                <!-- Título -->
                <h2 class="text-3xl md:text-4xl font-extrabold text-indigo-900 mb-4">
                    Se o seu escritório ainda atende manualmente,<br class="hidden md:block">
                    você está perdendo tempo e oportunidades.
                </h2>

                <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-16">
                    Veja como o <span class="font-semibold text-indigo-700">FacilitAI</span> transforma o atendimento jurídico em uma operação eficiente, inteligente e lucrativa.
                </p>

                <!-- Grid de Benefícios -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <!-- Card 1 -->
                    <div class="group bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-emerald-100">
                        <div class="w-16 h-16 mx-auto mb-6 bg-emerald-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Economize horas todos os dias</h3>
                        <p class="text-gray-600 leading-relaxed">Seu assistente responde dúvidas e agenda consultas automaticamente, liberando tempo da equipe.</p>
                    </div>

                    <!-- Card 2 -->
                    <div class="group bg-gradient-to-br from-indigo-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-indigo-100">
                        <div class="w-16 h-16 mx-auto mb-6 bg-indigo-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Atenda 24 horas por dia</h3>
                        <p class="text-gray-600 leading-relaxed">Mesmo fora do expediente, seu escritório continua captando e qualificando novos clientes.</p>
                    </div>

                    <!-- Card 3 -->
                    <div class="group bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-emerald-100">
                        <div class="w-16 h-16 mx-auto mb-6 bg-emerald-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Organize suas demandas</h3>
                        <p class="text-gray-600 leading-relaxed">Cada conversa é registrada, categorizada e pronta para ser encaminhada ao advogado certo.</p>
                    </div>

                    <!-- Card 4 -->
                    <div class="group bg-gradient-to-br from-indigo-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-indigo-100">
                        <div class="w-16 h-16 mx-auto mb-6 bg-indigo-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Atendimento profissional</h3>
                        <p class="text-gray-600 leading-relaxed">Personalize as respostas com a linguagem jurídica e o tom de voz do seu escritório.</p>
                    </div>

                    <!-- Card 5 -->
                    <div class="group bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-emerald-100 md:col-span-2 lg:col-span-1">
                        <div class="w-16 h-16 mx-auto mb-6 bg-emerald-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Reduza custos operacionais</h3>
                        <p class="text-gray-600 leading-relaxed">Automatize tarefas repetitivas — seu escritório se torna mais ágil e rentável.</p>
                    </div>
                </div>
            </div>
        </section>

       <!-- Bloco 3: Explicando o que é -->
        <section id="o-que-e" class="py-20 bg-slate-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 text-center">

                <!-- Título principal -->
                <h2 class="text-3xl md:text-4xl font-extrabold text-indigo-900 mb-4">
                    Muito mais que um chatbot — o novo padrão de atendimento jurídico.
                </h2>

                <!-- Subtítulo -->
                <p class="text-lg text-indigo-700 font-medium mb-10 max-w-2xl mx-auto">
                    Um assistente que entende Direito, conversa como humano e nunca perde um cliente.
                </p>

                <!-- Texto introdutório -->
                <p class="text-lg text-gray-700 max-w-3xl mx-auto mb-12 leading-relaxed">
                    O <span class="font-semibold text-indigo-700">FacilitAI</span> é um assistente virtual jurídico que conversa com seus clientes de forma natural, empática e com domínio completo sobre o contexto legal.
                    Ele se conecta ao seu <span class="font-semibold text-emerald-600">WhatsApp de atendimento</span>, responde dúvidas, coleta informações e agenda consultas automaticamente — enquanto você foca nas audiências e tarefas realmente importantes.
                </p>

                <!-- Grid de Benefícios -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    
                    <!-- Card 1 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-emerald-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.615 3.385a6 6 0 010 7.23C16.275 21.6 14.288 22 12 22s-4.275-.4-5.615-1.385a6 6 0 010-7.23C7.725 11.6 9.712 11.2 12 11.2s4.275.4 5.615 1.385z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Não é um bot</h3>
                        <p class="text-gray-600 leading-relaxed">Conversa com empatia, entende o contexto e conduz o cliente como um humano de verdade.</p>
                    </div>

                    <!-- Card 2 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-indigo-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.206 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.794 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.794 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.206 18 16.5 18s-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Conhecimento jurídico completo</h3>
                        <p class="text-gray-600 leading-relaxed">Domina linguagem técnica e está preparado para lidar com áreas como cível, trabalhista e previdenciário.</p>
                    </div>

                    <!-- Card 3 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-emerald-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Atendimento natural</h3>
                        <p class="text-gray-600 leading-relaxed">Os clientes acreditam que estão falando com uma pessoa real — linguagem clara, profissional e empática.</p>
                    </div>

                    <!-- Card 4 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-indigo-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h8M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Agenda e qualifica automaticamente</h3>
                        <p class="text-gray-600 leading-relaxed">Transforma conversas em agendamentos, qualificando cada lead para o atendimento humano.</p>
                    </div>

                    <!-- Card 5 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-emerald-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V3m0 5V13m0 9v-5m-6 0H2M7 13s.552-4 4-4h4c3.448 0 4 4 4 4M7 13H3c-1.105 0-2 .895-2 2v2a2 2 0 002 2h4c1.105 0 2-.895 2-2v-2a2 2 0 00-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Capte novos clientes automaticamente</h3>
                        <p class="text-gray-600 leading-relaxed">Seu WhatsApp trabalha sozinho 24/7, garantindo que nenhum lead fique sem resposta.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bloco 4: Comparativo: Antes x Depois -->
        <section id="comparativo" class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="mb-10 text-center">
                <h2 class="text-3xl md:text-4xl font-extrabold text-indigo-900">Antes x Depois com o FacilitAI</h2>
                <p class="text-gray-600 mt-3">Veja na prática o impacto no seu dia a dia: menos papelada, mais tempo e clientes melhor atendidos.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                <!-- ANTES -->
                <figure class="relative bg-slate-50 rounded-2xl overflow-hidden shadow-sm">
                    <!-- Selo -->
                    <span class="absolute top-4 left-4 z-10 inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold bg-rose-600 text-white">
                    Antes
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />

                    </svg>
                    </span>

                    <!-- Imagem -->
                    <img
                    src="{{ asset('storage/lp_adv/antes2.webp') }}"
                    alt="Advogado sobrecarregado com papelada e respondendo WhatsApp"
                    class="w-full h-72 md:h-96 object-cover">

                    <!-- Texto -->
                    <figcaption class="p-6">
                    <h3 class="text-xl font-semibold text-indigo-900">Atendimento manual, tempo perdido</h3>
                    <ul class="mt-3 space-y-2 text-gray-700">
                        <li class="flex gap-2">
                        <span class="text-rose-500">•</span> Responde as mesmas dúvidas várias vezes ao dia
                        </li>
                        <li class="flex gap-2">
                        <span class="text-rose-500">•</span> Interrupções constantes e atraso nas tarefas
                        </li>
                        <li class="flex gap-2">
                        <span class="text-rose-500">•</span> Leads frios quando a resposta demora
                        </li>
                    </ul>
                    </figcaption>
                </figure>

                <!-- DEPOIS -->
                <figure class="relative bg-slate-50 rounded-2xl overflow-hidden shadow-md">
                    <!-- Selo -->
                    <span class="absolute top-4 left-4 z-10 inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-semibold bg-emerald-600 text-white">
                    Depois com FacilitAI
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    </span>

                    <!-- Imagem -->
                    <img
                    src="{{ asset('storage/lp_adv/depois.webp') }}"
                    alt="Advogado tranquilo, sem papelada, com escritório organizado"
                    class="w-full h-72 md:h-96 object-cover">

                    <!-- Texto -->
                    <figcaption class="p-6">
                    <h3 class="text-xl font-semibold text-indigo-900">Atendimento inteligente, tempo ganho</h3>
                    <ul class="mt-3 space-y-2 text-gray-700">
                        <li class="flex gap-2">
                        <span class="text-emerald-500">•</span> <strong>Atende melhor que humano</strong>: linguagem natural e empática
                        </li>
                        <li class="flex gap-2">
                        <span class="text-emerald-500">•</span> <strong>Capta novos clientes automaticamente</strong>
                        </li>
                        <li class="flex gap-2">
                        <span class="text-emerald-500">•</span> <strong>Nunca mais perca leads</strong> por falta de resposta (24/7)
                        </li>
                        <li class="flex gap-2">
                        <span class="text-emerald-500">•</span> <strong>Reduz tempo e custos</strong> operacionais do escritório
                        </li>
                    </ul>
                    </figcaption>
                </figure>
                </div>

                <!-- Mini-prova de valor em números (opcional) -->
                <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                <div class="bg-slate-50 rounded-xl p-6">
                    <div class="text-3xl font-extrabold text-indigo-900">-70%</div>
                    <div class="text-gray-600">tempo gasto com respostas repetitivas</div>
                </div>
                <div class="bg-slate-50 rounded-xl p-6">
                    <div class="text-3xl font-extrabold text-indigo-900">24/7</div>
                    <div class="text-gray-600">atendimento contínuo no WhatsApp</div>
                </div>
                <div class="bg-slate-50 rounded-xl p-6">
                    <div class="text-3xl font-extrabold text-indigo-900">+Clientes</div>
                    <div class="text-gray-600">conversões de leads qualificados</div>
                </div>
                </div>

                <!-- CTA (opcional) -->
                <div class="mt-10 text-center">
                <a href="" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/facilitai/assistentes-de-ia-para-whatsapp?hide_gdpr_banner=1'});return false;"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow">
                    Quero esse “depois” no meu escritório
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"/>
                    </svg>
                </a>
                </div>
            </div>
        </section>

        <!-- Bloco 5: Call-to-Action final -->
        <section id="cta-final" class="relative py-24 bg-gradient-to-br from-indigo-900 via-indigo-800 to-indigo-700 text-white overflow-hidden">
            <!-- Efeitos decorativos de fundo -->
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-96 h-96 bg-emerald-500 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-500 rounded-full blur-3xl"></div>
            </div>

            <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center relative z-10">
                <!-- Título principal -->
                <h2 class="text-3xl md:text-5xl font-extrabold mb-6 leading-tight">
                    Transforme o atendimento do seu escritório com Inteligência Artificial
                </h2>

                <!-- Subtítulo -->
                <p class="text-xl md:text-2xl text-indigo-100 mb-8 max-w-3xl mx-auto">
                    Deixe o FacilitAI cuidar dos seus clientes — e você, do que realmente importa.
                </p>

                <!-- Benefícios rápidos -->
                <div class="flex flex-wrap justify-center gap-6 mb-10 text-emerald-300">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Demonstração gratuita</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Pronto em 48 horas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Sem necessidade de conhecimento técnico</span>
                    </div>
                </div>

                <!-- CTA Button -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/facilitai/assistentes-de-ia-para-whatsapp?hide_gdpr_banner=1'});return false;"
                        class="group inline-flex items-center gap-3 px-8 py-4 bg-emerald-500 hover:bg-emerald-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-emerald-500/50 transition-all transform hover:scale-105">
                        <span>Agende sua demonstração gratuita</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>

                <!-- Texto adicional -->
                <p class="mt-8 text-indigo-200 text-sm">
                    👉 Veja o FacilitAI em ação e descubra como ele pode revolucionar seu escritório
                </p>
            </div>
        </section>

        <!-- Bloco 6: Perguntas Frequentes -->
        <section id="faq" class="py-20 bg-slate-50">
            <div class="max-w-4xl mx-auto px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-indigo-900 mb-4">
                        Perguntas Frequentes
                    </h2>
                    <p class="text-lg text-gray-600">
                        Tire suas dúvidas sobre o FacilitAI
                    </p>
                </div>

                <div class="space-y-4" x-data="{ aberto: null }" x-cloak>
                    <!-- Pergunta 1 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 1 ? null : 1"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">O FacilitAI é só mais um robô de respostas automáticas?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 1 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 1" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Não. O FacilitAI usa inteligência artificial real — ele entende contexto, se adapta à conversa e responde como um humano. Os clientes não percebem que estão falando com um assistente virtual.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 2 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 2 ? null : 2"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Preciso saber programar para usar o FacilitAI?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 2 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 2" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                De forma alguma. Nossa equipe especializada cuida de toda a configuração e implementação para você. Basta nos informar as necessidades do seu escritório e deixamos tudo funcionando perfeitamente.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 3 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 3 ? null : 3"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">O FacilitAI funciona com o WhatsApp que já uso no escritório?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 3 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 3" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. Ele se conecta diretamente ao seu WhatsApp de atendimento, sem precisar trocar número nem criar outro chip.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 4 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 4 ? null : 4"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Que tipo de perguntas o FacilitAI consegue responder?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 4 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 4" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Ele pode responder dúvidas comuns dos clientes, coletar informações, agendar consultas e até direcionar casos específicos para o advogado responsável.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 5 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 5 ? null : 5"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">O assistente tem conhecimento jurídico mesmo?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 5 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 5" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. O modelo do FacilitAI foi treinado para compreender linguagem jurídica e termos técnicos das principais áreas do Direito — civil, trabalhista, previdenciário e outras.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 6 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 6 ? null : 6"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Posso personalizar as respostas e o tom de voz?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 6 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 6" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. Você pode ajustar o estilo de atendimento para refletir a linguagem e a identidade do seu escritório.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 7 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 7 ? null : 7"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">O atendimento é seguro e confidencial?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 7 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 7" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Totalmente. O FacilitAI segue padrões rigorosos de segurança e não armazena dados sensíveis de forma pública. As informações permanecem protegidas dentro do seu ambiente de atendimento.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 8 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 8 ? null : 8"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Quanto tempo leva para começar a usar?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 8 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 8" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Em apenas 48 horas, nossa equipe configura e personaliza todo o sistema. Após esse período, o assistente está pronto para começar a atender seus clientes automaticamente.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 9 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 9 ? null : 9"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Posso testar antes de contratar?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform" :class="aberto === 9 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 9" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. Você pode agendar uma demonstração gratuita e ver o FacilitAI funcionando antes de tomar qualquer decisão.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- RODAPÉ -->
    <footer class="py-10 text-center bg-slate-100 text-gray-600 text-sm">
        © {{ date('Y') }} FacilitAI. Todos os direitos reservados.
    </footer>

    <!-- Widget de link do Calendly - início -->
    <link href="https://assets.calendly.com/assets/external/widget.css" rel="stylesheet">
    <script src="https://assets.calendly.com/assets/external/widget.js" type="text/javascript" async></script>
    <!-- Widget de link do Calendly - fim -->
</body>
</html>
