@php
    $marketingWhatsappNumber = preg_replace('/\D/', '', (string) config('services.marketing.whatsapp')) ?: '';
    $marketingDemoUrl = $marketingWhatsappNumber !== ''
        ? 'https://wa.me/' . $marketingWhatsappNumber . '?text=' . rawurlencode('Olá quero saber mais sobre o FacilitAI.')
        : '#';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FacilitAI - Crie Assistentes de IA para WhatsApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <meta name="description" content="Crie assistentes de IA para WhatsApp em minutos com o FacilitAI. Conecte, automatize e venda no seu preço — sem precisar programar.">
    <meta property="og:title" content="FacilitAI - Crie Assistentes de IA para WhatsApp">
    <meta property="og:description" content="Transforme seu WhatsApp em um atendente inteligente que responde, envia vídeos e fecha vendas automaticamente.">
    <meta property="og:image" content="{{ asset('storage/homepage/facilitaAI.webp') }}">
    <meta property="og:type" content="website">

    
    <!-- Inclua o CSS do Tailwind que já está sendo compilado pelo Laravel Mix/Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-900 bg-slate-50 leading-relaxed scroll-smooth">
<div class="relative flex flex-col min-h-screen items-center overflow-hidden bg-gradient-to-br from-purple-700 to-blue-500 md:flex-row">
    <!-- Pattern de fundo -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,<svg width=&quot;60&quot; height=&quot;60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;><circle cx=&quot;30&quot; cy=&quot;30&quot; r=&quot;2&quot; fill=&quot;rgba(255,255,255,0.1)&quot;/></svg>')] animate-[float_15s_linear_infinite] opacity-40"></div>

    <div class="container relative z-10 mx-auto flex flex-col items-center justify-between px-5 max-w-5xl text-center md:flex-row md:text-left">
        <!-- Texto -->
        <div class="md:w-1/2 space-y-8 mt-10 md:mt-0">
            <div class="mb-8 md:mb-10">
                <img src="{{ asset('storage/homepage/facilitAI-branco2.png') }}" 
                     alt="FacilitAI Logo" 
                     class="mx-auto md:mx-0 w-[120px] md:w-[180px] drop-shadow-lg">
            </div>
            <h1 class="text-4xl font-extrabold leading-tight text-white drop-shadow-xl md:text-6xl">
                Crie assistentes de IA e conecte ao seu WhatsApp em <br class="hidden md:block">5 minutos.
            </h1>
            <p class="max-w-lg text-lg font-normal text-white text-opacity-90 md:text-xl mx-auto md:mx-0">
                Tenha um atendente virtual que responde seus clientes, envia áudios, vídeos e fecha vendas automaticamente —
                <span class="rounded-lg bg-white bg-opacity-25 px-3 py-1 font-semibold shadow-md">
                    Tudo isso com poucos cliques e sem precisar programar.
                </span>
            </p>
            <a href="#planos" class="inline-block rounded-xl bg-emerald-500 px-10 py-4 text-lg font-bold text-white shadow-lg shadow-emerald-500/40 transition-all duration-300 hover:-translate-y-1 hover:bg-emerald-600 hover:shadow-emerald-500/55">
                Crie seu primeiro assistente grátis e veja funcionando 🚀
            </a>
        </div>

        <!-- Imagem de demonstração -->
        <div class="w-full md:w-1/2 flex justify-center mt-12 md:mt-0">
            <img src="{{ asset('storage/homepage/e.webp') }}" 
                 alt="Demonstração de conversa WhatsApp" 
                 class="max-w-[350px] sm:max-w-[400px] md:max-w-[500px]">
        </div>
    </div>
</div>



    <!-- BLOCO: COMO É FÁCIL CRIAR SEU ASSISTENTE -->
    <section class="bg-white py-20">
        <div class="max-w-5xl mx-auto px-6 lg:px-8 text-center">
            
            <!-- Título -->
            <h2 class="text-3xl md:text-4xl font-extrabold text-indigo-900 mb-4">
            Crie seu assistente em apenas <span class="text-purple-600">2 passos simples</span>
            </h2>
            <p class="text-gray-600 max-w-2xl mx-auto mb-12">
            Você não precisa saber programar. Em poucos minutos seu assistente já está ativo e respondendo no WhatsApp.
            </p>

            <!-- Passo 1 -->
            <div class="grid lg:grid-cols-2 gap-10 items-center mb-16">
            <div class="text-left space-y-4">
                <h3 class="text-2xl font-semibold text-indigo-800">1️⃣ Crie seu assistente</h3>
                <ul class="text-gray-700 space-y-2">
                <li>• Escolha o nome e a personalidade do seu assistente.</li>
                <li>• Defina o tipo de atendimento (vendas, suporte, agendamento, etc).</li>
                <li>• Clique em <strong>"Criar"</strong> e pronto — seu assistente já está ativo!</li>
                </ul>
                <p class="text-emerald-600 font-medium mt-2">💡 Leva menos de 1 minuto para criar o primeiro!</p>
            </div>
            
            <!-- Vídeo 1 -->
            <div class="rounded-2xl overflow-hidden shadow-lg border border-gray-200">
                <video class="w-full h-auto" autoplay muted loop playsinline>
                <source src="{{ asset('storage/homepage/demontracao_criando_assistente.mp4') }}" type="video/mp4">
                </video>
            </div>
            </div>

            <!-- Passo 2 -->
            <div class="grid lg:grid-cols-2 gap-10 items-center">
            <!-- Vídeo 2 -->
            <div class="rounded-2xl overflow-hidden shadow-lg border border-gray-200 lg:order-1 order-2">
                <video class="w-full h-auto" autoplay muted loop playsinline>
                <source src="{{ asset('storage/homepage/demontracao_conectando.mp4') }}" type="video/mp4">
                </video>
            </div>

            <div class="text-left space-y-4 lg:order-2 order-1">
                <h3 class="text-2xl font-semibold text-indigo-800">2️⃣ Conecte ao seu WhatsApp</h3>
                <ul class="text-gray-700 space-y-2">
                <li>• Escaneie o QR Code com seu celular.</li>
                <li>• Em segundos, seu número estará conectado.</li>
                <li>• Seu assistente começa a responder automaticamente.</li>
                </ul>
                <p class="text-emerald-600 font-medium mt-2">⚡ Simples assim: criou, conectou, começou a vender.</p>
            </div>
            </div>

            <!-- Frase final -->
            <p class="text-lg text-gray-700 mt-16 font-medium">
            Em menos de <span class="text-purple-600 font-bold">5 minutos</span>, seu WhatsApp se transforma em um atendente inteligente que trabalha por você 24h por dia.
            </p>

            <!-- CTA -->
            <div class="mt-10">
            <a href="#planos" 
                class="inline-block bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-4 px-8 rounded-full shadow-lg hover:scale-105 transition-transform">
                🚀 Criar meu assistente agora
            </a>
            </div>

        </div>
    </section>

    <section class="relative bg-white py-24">
        <div class="container mx-auto flex flex-col items-center gap-16 px-6 max-w-5xl lg:flex-row lg:items-center lg:gap-20">
            
            <!-- VÍDEO DEMONSTRATIVO -->
            <div class="relative w-full max-w-[600px] overflow-hidden rounded-3xl shadow-2xl border border-slate-200">
            <video autoplay muted loop playsinline class="w-full rounded-3xl">
                <source src="{{ asset('storage/homepage/demontracao_conversa-6.mp4') }}" type="video/mp4">
                Seu navegador não suporta vídeos.
            </video>
            </div>

            <!-- TEXTO -->
            <div class="w-full max-w-xl space-y-8 text-center lg:text-left">
            <h2 class="text-3xl font-extrabold text-slate-900 md:text-4xl">
                Um assistente que entende, responde e encanta seus clientes 💬
            </h2>
            <p class="text-lg text-slate-600">
                Veja na prática como a IA conversa de forma natural, entende mensagens, reconhece imagens e entrega respostas em segundos — tudo dentro do WhatsApp.
            </p>

            <div class="space-y-6">
                <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 text-xl">🎧</div>
                <p><strong>Escuta áudios:</strong> entende o que o cliente fala e responde automaticamente — economizando tempo do atendimento humano.</p>
                </div>
                <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 text-xl">🖼️</div>
                <p><strong>Lê imagens:</strong> identifica fotos de produtos, documentos ou animais — ideal para clínicas, petshops e lojas.</p>
                </div>
                <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-purple-600 text-xl">🎤</div>
                <p><strong>Envia áudios:</strong> responde com voz humanizada, tornando a conversa mais próxima e natural.</p>
                </div>
                <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-100 text-pink-600 text-xl">🎬</div>
                <p><strong>Envia vídeos:</strong> apresenta produtos, faz demonstrações e reforça a credibilidade da marca — sem precisar de vendedor online.</p>
                </div>
            </div>
            </div>
        </div>
    </section>

    <!-- SEÇÃO: AGENDAR DEMONSTRAÇÃO -->
    <section id="planos" class="bg-gradient-to-br from-slate-900 to-[#2A3B4D] py-24">
    <div class="container mx-auto px-6 max-w-5xl text-center">
        <h2 class="relative mb-8 text-4xl font-bold text-white after:absolute after:bottom-[-15px] after:left-1/2 after:h-1 after:w-20 after:-translate-x-1/2 after:rounded-md after:bg-gradient-to-r after:from-blue-500 after:to-emerald-500 md:text-3xl">
        Agende uma Demonstração
        </h2>
        <p class="text-slate-300 max-w-2xl mx-auto mb-16 text-lg leading-relaxed">
        Converse com nossa equipe e veja na prática como o FacilitAI funciona.  
        Tire todas as suas dúvidas e descubra como criar e vender assistentes de IA pelo WhatsApp.
        </p>

        <!-- CARD DE DEMONSTRAÇÃO -->
        <div class="mx-auto max-w-lg relative flex flex-col rounded-2xl border-2 border-slate-700 bg-slate-800/60 p-10 backdrop-blur-md shadow-xl shadow-black/30 transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:shadow-emerald-500/20">
        <div class="mb-5 inline-flex rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-3 mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-white">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5m-7.5 3h7.5m-7.5 3h4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h3 class="mb-3 text-3xl font-bold text-white">Demonstração Personalizada</h3>
        <p class="mb-6 text-slate-300">Agende uma conversa rápida e descubra como o FacilitAI pode transformar seu atendimento e suas vendas.</p>

        <div class="mb-8 flex-grow border-b border-slate-700 pb-8 text-left mx-auto max-w-sm">
            <div class="mb-3 flex items-start gap-2">
            <i data-lucide="check" class="mt-0.5 h-5 w-5 text-green-400"></i>
            <span class="text-sm text-slate-300">Demonstração guiada por um especialista</span>
            </div>
            <div class="mb-3 flex items-start gap-2">
            <i data-lucide="check" class="mt-0.5 h-5 w-5 text-green-400"></i>
            <span class="text-sm text-slate-300">Tire dúvidas e veja o sistema funcionando ao vivo</span>
            </div>
            <div class="mb-3 flex items-start gap-2">
            <i data-lucide="check" class="mt-0.5 h-5 w-5 text-green-400"></i>
            <span class="text-sm text-slate-300">Sem compromisso — conheça antes de contratar</span>
            </div>
        </div>

        <a href="{{ $marketingDemoUrl }}"
            target="_blank"
            class="inline-block w-full rounded-lg bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-4 text-center font-semibold text-white shadow-md hover:from-emerald-600 hover:to-emerald-700 hover:shadow-lg hover:shadow-emerald-500/30 transition-all duration-300">
            💬 Agendar Demonstração via WhatsApp
        </a>
        </div>

        <p class="text-slate-400 text-sm mt-6">
        *Atendimento de segunda a sexta das 9h às 18h (horário de Brasília).
        </p>
    </div>
    </section>



    <section id="faq" class="bg-slate-50 py-24">
        <div class="container mx-auto px-6 max-w-5xl">
            <h2 class="text-center text-4xl font-extrabold text-slate-900 mb-12">
            Dúvidas frequentes
            </h2>

            <div x-data="{ open: null }" class="space-y-4">
            
            <!-- Pergunta 1 -->
            <div class="border border-slate-200 rounded-2xl bg-white shadow-sm overflow-hidden">
                <button @click="open === 1 ? open = null : open = 1" class="w-full flex justify-between items-center px-6 py-5 text-left text-lg font-semibold text-slate-800">
                <span>Posso substituir totalmente minha equipe de vendas e atendimento por Agentes IA?</span>
                <span x-text="open === 1 ? '−' : '+'" class="text-2xl text-purple-700"></span>
                </button>
                <div x-show="open === 1" x-collapse class="px-6 pb-6 text-slate-600 leading-relaxed">
                O ideal é que os Agentes IA trabalhem em conjunto com sua equipe — automatizando respostas, triando clientes e cuidando dos contatos fora do horário comercial.  
                Assim, sua equipe foca nas vendas de maior valor enquanto a IA cuida do restante.
                </div>
            </div>

            <!-- Pergunta 2 -->
            <div class="border border-slate-200 rounded-2xl bg-white shadow-sm overflow-hidden">
                <button @click="open === 2 ? open = null : open = 2" class="w-full flex justify-between items-center px-6 py-5 text-left text-lg font-semibold text-slate-800">
                <span>É fácil treinar um Agente IA?</span>
                <span x-text="open === 2 ? '−' : '+'" class="text-2xl text-purple-700"></span>
                </button>
                <div x-show="open === 2" x-collapse class="px-6 pb-6 text-slate-600 leading-relaxed">
                Sim! O processo é intuitivo.  
                Você ensina o assistente da mesma forma que explicaria a um novo funcionário: adicionando exemplos de perguntas e respostas.  
                Em poucos minutos ele já começa a entender seu negócio.
                </div>
            </div>

            <!-- Pergunta 3 -->
            <div class="border border-slate-200 rounded-2xl bg-white shadow-sm overflow-hidden">
                <button @click="open === 3 ? open = null : open = 3" class="w-full flex justify-between items-center px-6 py-5 text-left text-lg font-semibold text-slate-800">
                <span>Vocês ajudam a treinar os Agentes IA e configurar os recursos do FacilitAI?</span>
                <span x-text="open === 3 ? '−' : '+'" class="text-2xl text-purple-700"></span>
                </button>
                <div x-show="open === 3" x-collapse class="px-6 pb-6 text-slate-600 leading-relaxed">
                Sim! Oferecemos suporte completo e treinamento guiado, além de uma aula gratuita explicando como criar, treinar e vender seus Agentes IA.  
                Você nunca fica sozinho — nosso time te ajuda a colocar tudo em funcionamento.
                </div>
            </div>

            <!-- Pergunta 4 -->
            <div class="border border-slate-200 rounded-2xl bg-white shadow-sm overflow-hidden">
                <button @click="open === 4 ? open = null : open = 4" class="w-full flex justify-between items-center px-6 py-5 text-left text-lg font-semibold text-slate-800">
                <span>Preciso de conhecimentos técnicos ou de programação?</span>
                <span x-text="open === 4 ? '−' : '+'" class="text-2xl text-purple-700"></span>
                </button>
                <div x-show="open === 4" x-collapse class="px-6 pb-6 text-slate-600 leading-relaxed">
                Não precisa saber programar.  
                O FacilitAI foi criado para pessoas comuns — com interface simples e visual.  
                Basta seguir o passo a passo e você já estará criando automações em minutos.
                </div>
            </div>

            <!-- Pergunta 5 -->
            <div class="border border-slate-200 rounded-2xl bg-white shadow-sm overflow-hidden">
                <button @click="open === 5 ? open = null : open = 5" class="w-full flex justify-between items-center px-6 py-5 text-left text-lg font-semibold text-slate-800">
                <span>Nossas vendas são complexas, o Agente IA conseguirá aprender?</span>
                <span x-text="open === 5 ? '−' : '+'" class="text-2xl text-purple-700"></span>
                </button>
                <div x-show="open === 5" x-collapse class="px-6 pb-6 text-slate-600 leading-relaxed">
                Sim! Quanto mais informações você fornece, mais o Agente IA aprende.  
                Ele pode ser treinado com scripts de vendas, PDFs, sites e mensagens anteriores, tornando-se um verdadeiro especialista no seu produto ou serviço.
                </div>
            </div>

            <!-- Pergunta 6 -->
            <div class="border border-slate-200 rounded-2xl bg-white shadow-sm overflow-hidden">
                <button @click="open === 6 ? open = null : open = 6" class="w-full flex justify-between items-center px-6 py-5 text-left text-lg font-semibold text-slate-800">
                <span>Posso atender clientes em inglês e outras línguas com IA?</span>
                <span x-text="open === 6 ? '−' : '+'" class="text-2xl text-purple-700"></span>
                </button>
                <div x-show="open === 6" x-collapse class="px-6 pb-6 text-slate-600 leading-relaxed">
                Sim! O FacilitAI entende e responde em diversos idiomas automaticamente.  
                Ideal para negócios que atendem turistas, importadores ou clientes internacionais.
                </div>
            </div>
            </div>
        </div>
    </section>    
  
    @include('homepage.footer') 


</body>
</html>
