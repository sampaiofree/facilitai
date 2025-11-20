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
<!-- Corpo da página -->
<body class="antialiased text-gray-800 bg-slate-50 opacity-0 transition-opacity duration-500"
      x-data="{ loaded: false }"
      x-init="$nextTick(() => { loaded = true; });"
      :class="{ 'opacity-100': loaded }">

    <!-- Preloader -->
    <div id="preloader"
         class="fixed inset-0 flex items-center justify-center bg-slate-50 z-[9999]"
         x-show="!loaded"
         x-cloak>
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
                        Automatize o atendimento da sua <span class="text-emerald-400">clínica odontológica</span> com Inteligência Artificial
                    </h1>

                    <p class="text-lg text-indigo-100">
                        Tenha um assistente virtual que responde dúvidas, envia orçamentos, agenda consultas e capta novos pacientes automaticamente — direto pelo WhatsApp, 24h por dia.
                    </p>

                    <a href="" class="whatsapp-link group inline-flex items-center gap-3 px-8 py-4 bg-emerald-500 hover:bg-emerald-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-emerald-500/50 transition-all transform hover:scale-105">
                        <span>Agende sua demonstração gratuita</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>

                <!-- Mockup imagem -->
                <div class="w-full lg:w-1/2 flex justify-center">
                    <img src="{{ asset('storage/lp_odont/whatsapp.webp') }}" alt="Assistente odontológico no WhatsApp"
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
                    Se sua clínica ainda atende manualmente,<br class="hidden md:block">
                    você está perdendo pacientes e produtividade.
                </h2>

                <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-16">
                    Veja como o <span class="font-semibold text-indigo-700">FacilitAI</span> transforma o atendimento odontológico em uma operação moderna, automatizada e humanizada — direto no WhatsApp.
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
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Economize tempo da recepção</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Seu assistente responde dúvidas sobre tratamentos, horários e valores, enquanto sua equipe foca no atendimento presencial.
                        </p>
                    </div>

                    <!-- Card 2 -->
                    <div class="group bg-gradient-to-br from-indigo-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-indigo-100">
                        <div class="w-16 h-16 mx-auto mb-6 bg-indigo-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Atendimento 24 horas por dia</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Mesmo fora do expediente, sua clínica continua agendando consultas e respondendo pacientes automaticamente.
                        </p>
                    </div>

                    <!-- Card 3 -->
                    <div class="group bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-emerald-100">
                        <div class="w-16 h-16 mx-auto mb-6 bg-emerald-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Organize seus agendamentos</h3>
                        <p class="text-gray-600 leading-relaxed">
                            O assistente coleta nome, telefone e tipo de tratamento desejado — deixando tudo pronto na sua agenda digital.
                        </p>
                    </div>

                    <!-- Card 4 -->
                    <div class="group bg-gradient-to-br from-indigo-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-indigo-100">
                        <div class="w-16 h-16 mx-auto mb-6 bg-indigo-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Atendimento profissional e humanizado</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Personalize a linguagem do assistente para refletir o estilo da sua clínica — acolhedor, técnico ou institucional.
                        </p>
                    </div>

                    <!-- Card 5 -->
                    <div class="group bg-gradient-to-br from-emerald-50 to-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-emerald-100 md:col-span-2 lg:col-span-1">
                        <div class="w-16 h-16 mx-auto mb-6 bg-emerald-500 rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3 text-indigo-900">Reduza custos e faltas</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Automatize confirmações de consulta e lembretes — diminuindo faltas e otimizando sua agenda.
                        </p>
                    </div>
                </div>
            </div>
        </section>


        <!-- Bloco 3: Explicando o que é -->
        <section id="o-que-e" class="py-20 bg-slate-50">
            <div class="max-w-6xl mx-auto px-6 lg:px-8 text-center">

                <!-- Título principal -->
                <h2 class="text-3xl md:text-4xl font-extrabold text-indigo-900 mb-4">
                    Muito mais que um chatbot — o novo padrão de atendimento odontológico.
                </h2>

                <!-- Subtítulo -->
                <p class="text-lg text-indigo-700 font-medium mb-10 max-w-2xl mx-auto">
                    Um assistente que entende seus pacientes, fala com empatia e mantém sua agenda sempre cheia.
                </p>

                <!-- Texto introdutório -->
                <p class="text-lg text-gray-700 max-w-3xl mx-auto mb-12 leading-relaxed">
                    O <span class="font-semibold text-indigo-700">FacilitAI</span> é um assistente virtual inteligente que conversa com seus pacientes de forma natural, acolhedora e eficiente.
                    Ele se conecta ao seu <span class="font-semibold text-emerald-600">WhatsApp de atendimento</span>, responde dúvidas sobre tratamentos, horários e valores, envia lembretes e agenda consultas automaticamente — enquanto sua equipe foca nos atendimentos presenciais.
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
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Não é um bot comum</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Fala com empatia, entende o contexto e conduz o paciente de forma natural — como um atendente humano de verdade.
                        </p>
                    </div>

                    <!-- Card 2 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-indigo-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.206 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.794 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.794 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.206 18 16.5 18s-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Conhecimento odontológico</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Treinado para compreender termos e tratamentos odontológicos — limpeza, implantes, clareamento, ortodontia e mais.
                        </p>
                    </div>

                    <!-- Card 3 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-emerald-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Atendimento acolhedor</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Os pacientes sentem que estão sendo ouvidos e cuidados — com respostas claras, gentis e personalizadas.
                        </p>
                    </div>

                    <!-- Card 4 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-indigo-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h8M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Agenda automática</h3>
                        <p class="text-gray-600 leading-relaxed">
                            O assistente coleta dados e agenda a consulta automaticamente, enviando lembretes no WhatsApp.
                        </p>
                    </div>

                    <!-- Card 5 -->
                    <div class="group bg-white border border-emerald-100 rounded-2xl p-8 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="w-14 h-14 mx-auto mb-5 flex items-center justify-center bg-emerald-500 text-white rounded-2xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V3m0 5V13m0 9v-5m-6 0H2M7 13s.552-4 4-4h4c3.448 0 4 4 4 4M7 13H3c-1.105 0-2 .895-2 2v2a2 2 0 002 2h4c1.105 0 2-.895 2-2v-2a2 2 0 00-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-indigo-900 mb-3">Captação de novos pacientes</h3>
                        <p class="text-gray-600 leading-relaxed">
                            Seu WhatsApp passa a captar e qualificar leads automaticamente — sem depender de campanhas manuais.
                        </p>
                    </div>
                </div>
            </div>
        </section>


        <!-- Bloco 4: Comparativo: Antes x Depois -->
        <section id="comparativo" class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="mb-10 text-center">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-indigo-900">Antes x Depois com o FacilitAI</h2>
                    <p class="text-gray-600 mt-3">Veja como o atendimento da sua clínica pode se transformar: menos correria, menos faltas e mais pacientes satisfeitos.</p>
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
                            src="{{ asset('storage/lp_odont/antes2.webp') }}"
                            alt="Recepcionista odontológica sobrecarregada com mensagens e ligações"
                            class="w-full h-72 md:h-96 object-cover">

                        <!-- Texto -->
                        <figcaption class="p-6">
                            <h3 class="text-xl font-semibold text-indigo-900">Atendimento manual e desorganizado</h3>
                            <ul class="mt-3 space-y-2 text-gray-700">
                                <li class="flex gap-2">
                                    <span class="text-rose-500">•</span> Responde as mesmas perguntas dezenas de vezes ao dia
                                </li>
                                <li class="flex gap-2">
                                    <span class="text-rose-500">•</span> Horários vagos e pacientes que esquecem da consulta
                                </li>
                                <li class="flex gap-2">
                                    <span class="text-rose-500">•</span> Dificuldade em acompanhar todas as mensagens
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
                            src="{{ asset('storage/lp_odont/depois2.webp') }}"
                            alt="Equipe odontológica tranquila, com atendimento automatizado"
                            class="w-full h-72 md:h-96 object-cover">

                        <!-- Texto -->
                        <figcaption class="p-6">
                            <h3 class="text-xl font-semibold text-indigo-900">Atendimento inteligente e automatizado</h3>
                            <ul class="mt-3 space-y-2 text-gray-700">
                                <li class="flex gap-2">
                                    <span class="text-emerald-500">•</span> <strong>Assistente 24h</strong> que responde pacientes automaticamente
                                </li>
                                <li class="flex gap-2">
                                    <span class="text-emerald-500">•</span> <strong>Confirma consultas e envia lembretes</strong> pelo WhatsApp
                                </li>
                                <li class="flex gap-2">
                                    <span class="text-emerald-500">•</span> <strong>Agenda sempre cheia</strong> e equipe mais produtiva
                                </li>
                                <li class="flex gap-2">
                                    <span class="text-emerald-500">•</span> <strong>Mais pacientes satisfeitos</strong> e menos custos operacionais
                                </li>
                            </ul>
                        </figcaption>
                    </figure>
                </div>

                <!-- Mini-prova de valor em números -->
                <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="text-3xl font-extrabold text-indigo-900">-60%</div>
                        <div class="text-gray-600">tempo gasto com respostas manuais</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="text-3xl font-extrabold text-indigo-900">24/7</div>
                        <div class="text-gray-600">atendimento ativo no WhatsApp</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="text-3xl font-extrabold text-indigo-900">+Agendamentos</div>
                        <div class="text-gray-600">com lembretes automáticos</div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="mt-10 text-center">
                    <a href="" class="whatsapp-link inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow">
                        Quero esse “depois” na minha clínica
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
                    Transforme o atendimento da sua clínica odontológica com Inteligência Artificial
                </h2>

                <!-- Subtítulo -->
                <p class="text-xl md:text-2xl text-indigo-100 mb-8 max-w-3xl mx-auto">
                    Deixe o FacilitAI cuidar dos agendamentos, confirmações e pacientes — enquanto você foca em sorrisos.
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
                        <span class="font-medium">Pronto em até 48 horas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Sem precisar saber nada de tecnologia</span>
                    </div>
                </div>

                <!-- CTA Button -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="" class="whatsapp-link group inline-flex items-center gap-3 px-8 py-4 bg-emerald-500 hover:bg-emerald-600 rounded-xl font-bold text-lg shadow-2xl hover:shadow-emerald-500/50 transition-all transform hover:scale-105">
                        <span>Agende sua demonstração gratuita</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>

                <!-- Texto adicional -->
                <p class="mt-8 text-indigo-200 text-sm">
                    💬 Conheça o FacilitAI e descubra como ele pode revolucionar o atendimento da sua clínica odontológica.
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
                        Tire suas dúvidas sobre o FacilitAI para clínicas odontológicas
                    </p>
                </div>

                <div class="space-y-4" x-data="{ aberto: null }" x-cloak>

                    <!-- Pergunta 1 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 1 ? null : 1"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">
                                O FacilitAI é só mais um robô de respostas automáticas?
                            </span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 1 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 1" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Não. O FacilitAI usa inteligência artificial real — ele entende contexto, adapta as respostas e conversa com o paciente como um atendente humano. 
                                O resultado é um atendimento empático, rápido e profissional.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 2 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 2 ? null : 2"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Preciso saber de tecnologia para usar o FacilitAI?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 2 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 2" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                De forma alguma. Nossa equipe configura tudo para você. Basta nos passar as informações da sua clínica e em até 48 horas o sistema estará pronto e funcionando.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 3 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 3 ? null : 3"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">O FacilitAI funciona com o WhatsApp que já uso na clínica?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 3 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 3" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. Ele se conecta ao número do WhatsApp que sua clínica já utiliza — sem precisar trocar chip ou criar outro contato.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 4 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 4 ? null : 4"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">O assistente entende termos odontológicos e tratamentos?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 4 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 4" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. Ele foi treinado para compreender e conversar sobre procedimentos como limpeza, implantes, próteses, ortodontia, clareamento e outros tratamentos.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 5 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 5 ? null : 5"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Posso personalizar as respostas e o tom de voz do assistente?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 5 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 5" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. O FacilitAI pode adotar o estilo que você quiser — mais técnico, mais humano ou mais acolhedor, combinando com a identidade da sua clínica.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 6 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 6 ? null : 6"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">O sistema envia lembretes e confirmações de consulta?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 6 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 6" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim! O FacilitAI confirma automaticamente os agendamentos e envia lembretes personalizados via WhatsApp, reduzindo faltas e otimizando sua agenda.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 7 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 7 ? null : 7"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Os dados dos pacientes ficam seguros?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 7 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 7" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Sim. Todas as informações são tratadas de forma segura e confidencial, seguindo padrões rígidos de proteção de dados.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 8 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 8 ? null : 8"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Em quanto tempo posso começar a usar?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 8 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 8" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Em até 48 horas. Nossa equipe faz toda a configuração e o assistente já começa a responder seus pacientes no WhatsApp.
                            </div>
                        </div>
                    </div>

                    <!-- Pergunta 9 -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <button @click="aberto = aberto === 9 ? null : 9"
                            class="w-full px-6 py-5 text-left flex items-center justify-between hover:bg-slate-50 transition">
                            <span class="font-semibold text-indigo-900 pr-4">Posso ver o sistema funcionando antes de contratar?</span>
                            <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 transition-transform"
                                :class="aberto === 9 ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="aberto === 9" x-collapse>
                            <div class="px-6 pb-5 text-gray-700">
                                Claro! Você pode agendar uma demonstração gratuita e ver o FacilitAI atuando em tempo real — do jeito que funcionaria na sua clínica.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- RODAPÉ -->
        <footer class="py-10 text-center bg-slate-100 text-gray-600 text-sm">
            © {{ date('Y') }} FacilitAI. Todos os direitos reservados.
        </footer>

    </main>

    

    <!-- Widget de link do Calendly - início -->
    <link href="https://assets.calendly.com/assets/external/widget.css" rel="stylesheet">
    <script src="https://assets.calendly.com/assets/external/widget.js" type="text/javascript" async></script>
    <!-- Widget de link do Calendly - fim -->

    <!-- ✅ Meta Pixel Code (dinâmico) -->
    <script>
    (function() {
        // Captura o parâmetro do pixel na URL antes de carregar o script
        const urlParams = new URLSearchParams(window.location.search);
        const pixelParam = urlParams.get('p');
        const pixelPadrao = "123456789012345"; // ← Seu pixel padrão
        const pixelID = pixelParam || pixelPadrao;

        // Injeta o script do Meta Pixel
        !(function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)})(window,document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');

        // Inicia apenas UM pixel (dinâmico)
        fbq('init', pixelID);
        fbq('track', 'PageView');

        // Salva o pixelID em window para reutilizar depois
        window.currentPixelID = pixelID;
    })();
    </script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const numeroParam = urlParams.get('n');
    const numeroPadrao = "5599999999999"; // ← Número padrão do WhatsApp
    const numero = numeroParam || numeroPadrao;

    const mensagem = encodeURIComponent("Quero saber mais sobre assistente IA para o WhatsApp");
    const linkWhatsApp = `https://wa.me/${numero}?text=${mensagem}`;
    const botoes = document.querySelectorAll('.whatsapp-link');

    botoes.forEach(botao => {
      botao.setAttribute('href', linkWhatsApp);
      botao.setAttribute('target', '_blank');

      // 🎯 Evento Lead no clique
      botao.addEventListener('click', () => {
        if (typeof fbq !== 'undefined') {
          fbq('track', 'Lead', { pixel_id: window.currentPixelID || 'desconhecido' });
        }
      });
    });
  });
</script>

</body>
</html>
