@php
    $ctaText = "Quero atendimento mais rápido no WhatsApp";
    $ctaTextMobile = "Quero atender mais rápido";

    $benefits = [
        'Responda clientes na hora (mesmo fora do horário)',
        'Pare de repetir as mesmas respostas todo dia',
        'Organize quem quer comprar e quem só está perguntando',
        'Você entra só quando precisar (orçamento/fechamento)',
    ];

    $proofs = [
        ['title' => 'Resposta rápida', 'text' => 'Cliente não fica esperando e você não perde oportunidade por demora.'],
        ['title' => 'Menos bagunça', 'text' => 'Atendimento mais organizado: perguntas comuns respondidas e pedidos encaminhados.'],
        ['title' => 'Você no controle', 'text' => 'Quando a conversa precisa de você, o atendimento te chama e você assume.'],
    ];

    $steps = [
        'Você ensina, nós configuramos',
        'O assistente começa a trabalhar',
        'Você recebe mais conversas respondidas e foca no fechamento',
    ];

    $stepTexts = [
        'Você nos explica como gosta de atender e quais dúvidas seus clientes sempre têm.',
        'Criamos o atendimento inteligente que fala do seu jeito e nunca deixa ninguém esperando.',
        'Enquanto o atendimento responde o básico e organiza as mensagens, você entra nas conversas que realmente pedem orçamento, negociação e decisão.',
    ];

    $faqs = [
        ['q' => 'Isso substitui minha equipe?', 'a' => 'Não. Ele cuida do repetitivo e encaminha para você/equipe quando precisar de atenção humana.'],
        ['q' => 'Funciona fora do horário?', 'a' => 'Sim. Mesmo fora do horário, o cliente recebe resposta e você não perde a oportunidade.'],
        ['q' => 'Preciso trocar meu número?', 'a' => 'Na maioria dos casos, não. Depende da sua operação e integração escolhida.'],
        ['q' => 'É difícil de usar?', 'a' => 'Não. A parte técnica fica com a gente. Você só acompanha os atendimentos e resultados.'],
        ['q' => 'Isso serve para o meu tipo de empresa?', 'a' => 'Serve quando você recebe mensagens com frequência e perde tempo com perguntas repetidas.'],
    ];

    $showcaseBullets = [
        'Resposta inicial mais rápida para o cliente não esfriar',
        'Perguntas repetidas deixam de tomar seu tempo',
        'Você assume só quando precisa de orçamento ou fechamento',
    ];

    $qualificationRows = [
        ['label' => 'Cidade', 'value' => $cidade],
        ['label' => 'O que mais te atrasa', 'value' => 'Perguntas repetidas, demora na resposta e cliente esfriando'],
        ['label' => 'Quando você assume', 'value' => 'Orçamento, negociação e fechamento'],
    ];

    $quickStats = [
        ['value' => '24/7', 'label' => 'atendimento ativo'],
        ['value' => 'Rápido', 'label' => 'resposta inicial'],
        ['value' => 'Você', 'label' => 'entra quando precisa'],
    ];

    $proofThemeClasses = [
        ['card' => 'from-emerald-50 to-white', 'badge' => 'bg-emerald-100 text-emerald-700 ring-emerald-200'],
        ['card' => 'from-blue-50 to-white', 'badge' => 'bg-blue-100 text-blue-700 ring-blue-200'],
        ['card' => 'from-amber-50 to-white', 'badge' => 'bg-amber-100 text-amber-700 ring-amber-200'],
    ];

    $stepThemeClasses = [
        ['accent' => 'bg-emerald-500', 'surface' => 'from-emerald-50 to-white'],
        ['accent' => 'bg-blue-500', 'surface' => 'from-blue-50 to-white'],
        ['accent' => 'bg-slate-900', 'surface' => 'from-slate-50 to-white'],
    ];

    $metaPixelId = preg_replace('/\D/', '', (string) config('services.meta.pixel_id')) ?: '';
    $heroImageUrl = asset('homepage/e.webp');
    $demoConversationVideoUrl = asset('homepage/demontracao_conversa-6.mp4');
    $presentationVideoEmbedUrl = 'https://www.youtube.com/embed/alsj-l6fL80?rel=0';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @if($metaPixelId !== '')
    <!-- Meta Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $metaPixelId }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"
    /></noscript>
    @endif
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap');
        html { scroll-behavior: smooth; }
        body { font-family: 'Manrope', sans-serif; }
        h1, h2, h3, .font-display { font-family: 'Space Grotesk', sans-serif; }
        .gradient-text {
            background: linear-gradient(90deg, #059669 0%, #2563eb 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .text-balance { text-wrap: balance; }
        summary::-webkit-details-marker { display: none; }
        .faq-icon::before { content: '+'; }
        details[open] .faq-icon::before { content: '-'; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased" data-cidade="{{ $cidade }}">
<div class="relative overflow-x-clip">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[560px] bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.16),_transparent_58%),radial-gradient(circle_at_85%_10%,_rgba(37,99,235,0.14),_transparent_50%)]"></div>

    <main>
        <section class="px-6 pt-10 pb-16 md:pt-14 md:pb-24">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto mb-6 flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-white/90 px-4 py-2 text-xs font-semibold text-emerald-700 shadow-sm">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    Atendimento no WhatsApp para empresas de {{ $cidade }}
                </div>

                <div class="mx-auto max-w-4xl text-center">
                    <h1 class="text-balance text-4xl font-bold leading-tight text-slate-900 md:text-5xl lg:text-6xl">
                        Atenda mais rápido no WhatsApp em {{ $cidade }} e pare de perder clientes por demora
                    </h1>
                    <p class="mx-auto mt-5 max-w-3xl text-base leading-7 text-slate-600 md:text-xl md:leading-8">
                        Deixe as respostas prontas para as perguntas mais comuns e foque só nos casos que precisam de você.
                    </p>
                </div>

                <div class="mt-10 grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="space-y-6">
                        <div class="rounded-3xl border border-white/80 bg-white p-6 shadow-xl shadow-slate-200/60 md:p-8">
                            <h2 class="text-2xl font-bold leading-tight text-slate-900 md:text-3xl">
                                O que você vai resolver no atendimento
                            </h2>
                            <ul class="mt-6 grid gap-3" aria-label="Benefícios principais">
                                @foreach($benefits as $item)
                                    <li class="flex items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-4 py-3 text-sm font-semibold text-slate-700 md:text-base">
                                        <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200">✓</span>
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
                                @if($whatsAppEnabled)
                                    <a class="js-wa-cta inline-flex min-h-14 items-center justify-center rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-3 text-center text-sm font-bold text-white shadow-lg shadow-emerald-500/25 transition hover:-translate-y-0.5 hover:shadow-emerald-500/35 md:text-base"
                                       href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" data-cta-position="hero">
                                        {{ $ctaText }}
                                    </a>
                                @else
                                    <span class="inline-flex min-h-14 items-center justify-center rounded-full bg-slate-200 px-6 py-3 text-sm font-semibold text-slate-500">
                                        WhatsApp temporariamente indisponível
                                    </span>
                                @endif
                                <p class="text-sm leading-6 text-slate-500">
                                    Você fala direto no WhatsApp com mensagem pronta para começar mais rápido.
                                </p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-white/80 bg-white p-6 shadow-lg shadow-slate-200/60 md:p-8">
                            <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                O que você vai entender
                            </div>
                            <h2 id="apresentacao-title" class="text-2xl font-bold leading-tight text-slate-900 md:text-3xl">
                                Entenda em 2 minutos como isso funciona
                            </h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">
                                Depois de ver a conversa no celular, aqui você entende como isso entra na rotina da sua empresa sem mudar seu jeito de atender.
                            </p>

                            <div class="mt-5 rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 to-white p-5">
                                <h3 class="text-lg font-bold text-slate-900">Explicação direta, sem enrolação</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    O foco é mostrar como responder mais rápido, organizar conversas e continuar no controle do atendimento quando chegar a hora de fechar.
                                </p>

                                <ul class="mt-4 space-y-2" aria-label="Pontos abordados na apresentação">
                                    @foreach($showcaseBullets as $item)
                                        <li class="flex items-start gap-2 text-sm font-medium text-slate-700">
                                            <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span>{{ $item }}</span>
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl border border-slate-100 bg-white px-4 py-3">
                                        <div class="text-sm font-bold text-slate-900">Sem termo técnico</div>
                                        <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">linguagem de rotina e resultado</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-100 bg-white px-4 py-3">
                                        <div class="text-sm font-bold text-slate-900">Aplicação prática</div>
                                        <div class="mt-1 text-xs font-medium uppercase tracking-wide text-slate-500">como usar no seu atendimento</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-200/60 md:p-6">
                            <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Apresentação completa
                            </div>
                            <h3 class="text-2xl font-bold leading-tight text-slate-900 md:text-3xl">Quer ver a explicação completa?</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">
                                Assista ao vídeo para entender como colocamos isso para funcionar na prática, sem você precisar aprender a parte técnica.
                            </p>

                            <div class="relative mt-5 aspect-video overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-lg">
                                <iframe
                                    src="{{ $presentationVideoEmbedUrl }}"
                                    title="Apresentação do atendimento no WhatsApp"
                                    class="absolute inset-0 h-full w-full"
                                    loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allowfullscreen>
                                </iframe>
                            </div>
                            <p class="mt-3 text-xs leading-5 text-slate-500 md:text-sm">
                                Vídeo de apresentação para quem quer entender o processo completo antes de falar no WhatsApp.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
                            <div class="grid gap-4 sm:grid-cols-3">
                                @foreach($quickStats as $stat)
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-center sm:text-left">
                                        <div class="font-display text-xl font-bold text-slate-900">{{ $stat['value'] }}</div>
                                        <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-5 grid gap-3">
                                @foreach($qualificationRows as $row)
                                    <div class="rounded-2xl border border-slate-100 bg-white px-4 py-3">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400">{{ $row['label'] }}</div>
                                        <div class="mt-1 text-sm font-semibold leading-5 text-slate-700">{{ $row['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white px-6 py-16 md:py-24" aria-label="Demonstração visual no WhatsApp">
            <div class="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
                <div class="order-2 space-y-6 lg:order-1">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Veja no celular (9:16)
                        </div>
                        <h2 class="mt-4 text-balance text-3xl font-bold leading-tight text-slate-900 md:text-4xl">
                            Demonstração visual no WhatsApp
                        </h2>
                        <p class="mt-3 text-base leading-7 text-slate-600">
                            Vídeo sem áudio para você ver a conversa acontecendo no formato real do celular, sem explicação técnica no meio.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 shadow-xl shadow-slate-200/60 md:p-5">
                        <div class="mx-auto max-w-[320px] rounded-[2rem] border border-slate-200 bg-slate-900 p-2 shadow-2xl shadow-slate-900/20">
                            <div class="relative overflow-hidden rounded-[1.5rem] bg-black">
                                <div class="pointer-events-none absolute left-1/2 top-2 z-10 h-1.5 w-24 -translate-x-1/2 rounded-full bg-white/20"></div>
                                <div class="absolute right-3 top-4 z-10 rounded-xl bg-slate-900/80 px-3 py-2 text-xs font-semibold text-white shadow-lg ring-1 ring-white/10">
                                    Resposta rápida
                                    <small class="mt-0.5 block text-[10px] font-medium text-slate-300">cliente recebe retorno logo</small>
                                </div>
                                <div class="absolute bottom-4 left-3 z-10 rounded-xl bg-slate-900/85 px-3 py-2 text-xs font-semibold text-white shadow-lg ring-1 ring-white/10">
                                    Você assume depois
                                    <small class="mt-0.5 block text-[10px] font-medium text-slate-300">orçamento e fechamento</small>
                                </div>
                                <div class="aspect-[9/16]">
                                    <video autoplay muted loop playsinline preload="metadata" class="h-full w-full object-cover" aria-label="Demonstração visual de atendimento no WhatsApp">
                                        <source src="{{ $demoConversationVideoUrl }}" type="video/mp4">
                                    </video>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-1 space-y-6 lg:order-2">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60 md:p-8">
                        <div class="grid gap-5 sm:grid-cols-[180px_1fr] sm:items-center">
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm">
                                <img src="{{ $heroImageUrl }}" alt="Exemplo de tela de atendimento no WhatsApp" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Exemplo de conversa</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Imagem estática para mostrar como as respostas podem ficar organizadas no WhatsApp da sua empresa.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-lg shadow-blue-100/40 md:p-8">
                        <h3 class="text-xl font-bold text-slate-900 md:text-2xl">Resultado prático para quem atende o dia todo</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">
                            Enquanto o atendimento responde o básico e organiza as mensagens, você entra nas conversas que realmente pedem orçamento, negociação e decisão.
                        </p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white bg-white/80 px-4 py-4">
                                <div class="text-sm font-bold text-slate-900">Cliente não esfria</div>
                                <div class="mt-1 text-sm text-slate-600">Resposta inicial mais rápida para o cliente não esfriar.</div>
                            </div>
                            <div class="rounded-2xl border border-white bg-white/80 px-4 py-4">
                                <div class="text-sm font-bold text-slate-900">Você ganha foco</div>
                                <div class="mt-1 text-sm text-slate-600">Você assume só quando precisa de orçamento ou fechamento.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="px-6 py-16 md:py-24" aria-labelledby="confianca-title">
            <div class="mx-auto max-w-6xl">
                <div class="mb-10 text-center">
                    <h2 id="confianca-title" class="text-balance text-3xl font-bold leading-tight text-slate-900 md:text-5xl">
                        O que muda no seu atendimento em {{ $cidade }}
                    </h2>
                    <p class="mx-auto mt-4 max-w-3xl text-base leading-7 text-slate-600">
                        Resultado prático para quem recebe mensagem o dia todo e perde tempo com perguntas repetidas ou demora para responder.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    @foreach($proofs as $index => $p)
                        @php($theme = $proofThemeClasses[$index % count($proofThemeClasses)])
                        <article class="rounded-3xl border border-slate-200 bg-gradient-to-br {{ $theme['card'] }} p-6 shadow-lg shadow-slate-200/50 transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl ring-1 {{ $theme['badge'] }} font-display text-lg font-bold">
                                {{ $index + 1 }}
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">{{ $p['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">{{ $p['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-white px-6 py-16 md:py-24" aria-labelledby="como-funciona-title">
            <div class="mx-auto max-w-6xl">
                <div class="mb-10 text-center">
                    <h2 id="como-funciona-title" class="text-balance text-3xl font-bold leading-tight text-slate-900 md:text-5xl">
                        Como isso entra no seu atendimento
                    </h2>
                    <p class="mx-auto mt-4 max-w-3xl text-base leading-7 text-slate-600">
                        Primeiro deixamos o básico pronto. Depois ajustamos com o que acontece no dia a dia, sem complicar sua rotina.
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    @foreach($steps as $i => $title)
                        @php($stepTheme = $stepThemeClasses[$i % count($stepThemeClasses)])
                        <article class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br {{ $stepTheme['surface'] }} p-6 shadow-lg shadow-slate-200/50">
                            <div class="absolute right-5 top-5 h-2.5 w-2.5 rounded-full {{ $stepTheme['accent'] }}"></div>
                            <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">
                                {{ $i + 1 }}
                            </div>
                            <h3 class="text-xl font-bold leading-snug text-slate-900">{{ $title }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600 md:text-base">{{ $stepTexts[$i] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="px-6 py-16 md:py-24" aria-labelledby="faq-title">
            <div class="mx-auto max-w-4xl">
                <div class="mb-10 text-center">
                    <h2 id="faq-title" class="text-3xl font-bold leading-tight text-slate-900 md:text-5xl">Perguntas frequentes</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-slate-600">
                        Dúvidas de quem quer responder mais rápido no WhatsApp sem aumentar equipe.
                    </p>
                </div>

                <div class="space-y-4">
                    @foreach($faqs as $faq)
                        <details class="js-faq-item overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" @if($loop->first) open @endif>
                            <summary data-faq-question="{{ $faq['q'] }}" class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-left text-sm font-bold text-slate-900 md:text-base">
                                <span>{{ $faq['q'] }}</span>
                                <span class="faq-icon inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700"></span>
                            </summary>
                            <div class="border-t border-slate-100 px-5 py-4 text-sm leading-6 text-slate-600 md:text-base">
                                <p>{{ $faq['a'] }}</p>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="px-6 pb-24 md:pb-28" aria-labelledby="cta-final-title">
            <div class="mx-auto max-w-6xl rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white shadow-2xl shadow-slate-900/20 md:p-12">
                <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-emerald-200">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            Último passo
                        </div>
                        <h2 id="cta-final-title" class="mt-4 text-balance text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
                            Quer atender mais rápido em {{ $cidade }} e parar de perder cliente por demora?
                        </h2>
                        <p class="mt-4 max-w-2xl text-base leading-7 text-slate-300">
                            Fale com a gente no WhatsApp, mostre como seu atendimento funciona hoje e veja como deixar o básico rodando sem tirar você do controle.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm md:p-6">
                        @if($whatsAppEnabled)
                            <a class="js-wa-cta inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-4 text-center text-base font-bold text-white shadow-lg shadow-emerald-500/30 transition hover:-translate-y-0.5 hover:shadow-emerald-500/40"
                               href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" data-cta-position="footer">
                                {{ $ctaText }}
                            </a>
                        @else
                            <span class="inline-flex w-full items-center justify-center rounded-xl bg-white/10 px-6 py-4 text-center text-base font-semibold text-slate-300">
                                Configure WHATSAPP_MARKETING
                            </span>
                        @endif

                        <p class="mt-4 text-sm leading-6 text-slate-300">
                            Você fala direto no WhatsApp com mensagem pronta para agilizar a conversa.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="fixed inset-x-3 z-50 md:hidden" style="bottom: calc(0.75rem + env(safe-area-inset-bottom));">
        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-2 shadow-2xl shadow-slate-300/50 backdrop-blur">
            @if($whatsAppEnabled)
                <a class="js-wa-cta flex min-h-12 items-center justify-center rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-3 text-center text-sm font-bold text-white shadow-lg shadow-emerald-500/30"
                   href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" data-cta-position="sticky_mobile">
                    {{ $ctaTextMobile }}
                </a>
            @else
                <span class="flex min-h-12 items-center justify-center rounded-xl bg-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-500">
                    WhatsApp indisponível
                </span>
            @endif
        </div>
    </div>
</div>

<script>
(() => {
    const cidade = @json($cidade, JSON_UNESCAPED_UNICODE);
    const pagePath = window.location.pathname;
    const base = { cidade, page_path: pagePath };
    const hasFbq = () => typeof window.fbq === 'function';
    const track = (eventName, extra = {}) => { if (hasFbq()) window.fbq('track', eventName, { ...base, ...extra }); };
    const trackCustom = (eventName, extra = {}) => { if (hasFbq()) window.fbq('trackCustom', eventName, { ...base, ...extra }); };

    document.querySelectorAll('.js-wa-cta').forEach((cta) => {
        cta.addEventListener('click', () => {
            const ctaPosition = cta.dataset.ctaPosition || 'unknown';
            track('Contact', { cta_position: ctaPosition });
            track('Lead', {
                cta_position: ctaPosition,
                lead_source: 'lp6_whatsapp_cta',
            });
        });
    });

    document.querySelectorAll('.js-faq-item summary').forEach((summary) => {
        summary.addEventListener('click', () => {
            trackCustom('LPFAQClick', {
                question: summary.dataset.faqQuestion || (summary.textContent || '').trim()
            });
        });
    });

    let sent75 = false;
    const onScroll = () => {
        if (sent75) return;
        const doc = document.documentElement;
        const top = window.scrollY || doc.scrollTop || 0;
        const view = window.innerHeight || doc.clientHeight || 0;
        const full = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0);
        const trackable = full - view;
        if (trackable <= 0) return;
        if ((top / trackable) >= 0.75) {
            sent75 = true;
            trackCustom('LPScroll75', { depth_percent: 75 });
            window.removeEventListener('scroll', onScroll);
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>
</body>
</html>
