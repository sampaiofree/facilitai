<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Assistente IA para WhatsApp - FacilitAI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    html { scroll-behavior: smooth; }
    .gradient-text {
      background: linear-gradient(to right, #059669, #2563eb);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
  </style>
</head>
<body class="bg-gradient-to-b from-gray-50 to-white text-gray-900 font-sans antialiased">

  <!-- HERO -->
  <section class="min-h-screen flex flex-col items-center justify-center text-center px-6 py-20 space-y-10" x-data="{ open: false }">
    <div class="max-w-5xl">
      <h1 class="text-4xl md:text-6xl font-extrabold leading-tight text-balance mb-6">
        Transforme o WhatsApp da sua empresa em um 
        <span class="gradient-text">atendente inteligente</span> que trabalha 24h por dia
      </h1>
      <p class="text-lg md:text-2xl text-gray-600 mb-12">
        Nosso Assistente de IA cuida do atendimento, responde clientes, envia materiais e até fecha vendas — tudo de forma automática e humanizada.
      </p>

      <!-- VIDEO -->
      <div class="relative mx-auto aspect-video max-w-4xl rounded-3xl overflow-hidden shadow-2xl border border-gray-200 mb-12">
        <iframe 
          src="https://www.youtube.com/embed/alsj-l6fL80?rel=0"
          title="Demonstração do Assistente de IA"
          class="absolute inset-0 w-full h-full"
          frameborder="0"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen>
        </iframe>
      </div>

      <!-- CTA -->
      <a href="https://wa.me/5562995772922?text=Olá quero saber mais sobre o FacilitAI." 
         class="inline-block bg-gradient-to-r from-emerald-500 to-emerald-600 text-white font-bold text-xl py-5 px-12 rounded-full shadow-lg hover:shadow-emerald-500/40 hover:-translate-y-1 transition-all duration-300">
         🗓️ Agendar minha demonstração gratuita
      </a>
      <p class="text-gray-500 text-sm mt-3">Sem custo, sem compromisso — veja ao vivo como seu atendimento pode ser automatizado.</p>
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
            <h2 class="text-4xl md:text-5xl font-extrabold mb-16 text-gray-900">
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

        <a href="https://wa.me/5562995772922?text=Olá quero saber mais sobre o FacilitAI."
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

  <!-- FEATURES -->
  <section class="py-24 px-6 bg-white">
    <div class="max-w-6xl mx-auto text-center">
      <h2 class="text-4xl md:text-5xl font-extrabold mb-16 text-gray-900">
        🚀 O que o <span class="gradient-text">Assistente de IA</span> faz por você
      </h2>

      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10 text-left">

        <!-- Envio -->
        <div class="rounded-3xl p-8 bg-gradient-to-br from-blue-50 to-blue-100 hover:shadow-lg transition">
          <div class="flex items-center gap-3 mb-6">
            <div class="bg-blue-600 text-white w-14 h-14 flex items-center justify-center rounded-full text-2xl">💬</div>
            <h3 class="text-2xl font-bold text-gray-900">Comunicação Inteligente</h3>
          </div>
          <ul class="space-y-3 text-gray-700">
            <li><strong>Envia imagens</strong> — catálogos, produtos ou orçamentos.</li>
            <li><strong>Envia áudios e vídeos</strong> — mensagens personalizadas para seus clientes.</li>
            <li><strong>Envia PDFs</strong> — catálogos, contratos e cardápios automáticos.</li>
          </ul>
        </div>

        <!-- Compreensão -->
        <div class="rounded-3xl p-8 bg-gradient-to-br from-purple-50 to-purple-100 hover:shadow-lg transition">
          <div class="flex items-center gap-3 mb-6">
            <div class="bg-purple-600 text-white w-14 h-14 flex items-center justify-center rounded-full text-2xl">🧠</div>
            <h3 class="text-2xl font-bold text-gray-900">Compreensão Avançada</h3>
          </div>
          <ul class="space-y-3 text-gray-700">
            <li><strong>Lê áudios</strong> — entende o que o cliente fala e responde em segundos.</li>
            <li><strong>Reconhece imagens</strong> — interpreta fotos e documentos enviados.</li>
          </ul>
        </div>

        <!-- Organização -->
        <div class="rounded-3xl p-8 bg-gradient-to-br from-emerald-50 to-emerald-100 hover:shadow-lg transition md:col-span-2 lg:col-span-1">
          <div class="flex items-center gap-3 mb-6">
            <div class="bg-emerald-600 text-white w-14 h-14 flex items-center justify-center rounded-full text-2xl">📅</div>
            <h3 class="text-2xl font-bold text-gray-900">Organização e Vendas</h3>
          </div>
          <ul class="space-y-3 text-gray-700">
            <li><strong>Agendamentos automáticos</strong> — ideal para salões e clínicas.</li>
            <li><strong>Conduz conversas de vendas</strong> — guia o cliente até o fechamento.</li>
            <li><strong>Consulta sistemas internos</strong> — estoque, CRM ou agenda.</li>
          </ul>
        </div>

      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section class="py-24 px-6 bg-gradient-to-br from-emerald-600 to-emerald-800 text-white text-center">
    <div class="max-w-4xl mx-auto">
      <h2 class="text-3xl md:text-4xl font-extrabold mb-8 leading-tight">
        💡 Imagine ter um atendente que nunca se atrasa, nunca esquece um cliente e trabalha 24h por dia.
      </h2>
      <p class="text-lg mb-10 opacity-90">
        Clique abaixo e veja na prática o que o <strong>Assistente de IA do FacilitAI</strong> pode fazer pela sua empresa.
      </p>
      <a href="https://wa.me/5562995772922?text=Olá quero saber mais sobre o FacilitAI." 
         class="inline-block bg-white text-emerald-600 font-bold text-xl py-5 px-12 rounded-full shadow-lg hover:-translate-y-1 hover:bg-gray-100 transition-all duration-300">
         🚀 Agendar minha demonstração gratuita
      </a>
    </div>
  </section>

  @include('homepage.footer') 
</body>
</html>
