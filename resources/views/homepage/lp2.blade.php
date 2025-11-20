<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>FacilitAI — Venda 100% no Automático pelo WhatsApp</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>[x-cloak]{display:none!important}</style>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
    body { font-family: 'Inter', sans-serif; }
    
    .gradient-hero {
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 25%, #312e81 50%, #4c1d95 75%, #5b21b6 100%);
      position: relative;
    }
    
    .gradient-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 20% 50%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.1) 0%, transparent 50%);
    }
    
    .gradient-text {
      background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .card-hover { 
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover { 
      transform: translateY(-8px); 
      box-shadow: 0 20px 40px rgba(0,0,0,0.12);
    }
    
    .fade-in {
      animation: fadeIn 0.8s ease-out forwards;
      opacity: 0;
    }
    
    @keyframes fadeIn {
      to { opacity: 1; }
    }
    
    .slide-up {
      animation: slideUp 0.8s ease-out forwards;
      opacity: 0;
      transform: translateY(30px);
    }
    
    @keyframes slideUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .pulse-glow {
      animation: pulseGlow 2s ease-in-out infinite;
    }
    
    @keyframes pulseGlow {
      0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
      50% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.6); }
    }
    
    .floating-cta {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 50;
      animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
    
    .stat-number {
      font-size: 3.5rem;
      font-weight: 900;
      line-height: 1;
    }
    
    .comparison-table td, .comparison-table th {
      padding: 1rem;
      text-align: center;
    }
  </style>
</head>

<body class="antialiased text-gray-800 bg-slate-50">

  <!-- FLOATING CTA -->
  <div class="floating-cta hidden md:block" x-data="{ visible: false }" 
       x-init="setTimeout(() => visible = true, 3000)"
       x-show="visible" 
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4"
       x-transition:enter-end="opacity-100 translate-y-0">
    <a href="#planos" class="flex items-center gap-3 px-6 py-4 bg-emerald-500 text-white rounded-full shadow-2xl hover:bg-emerald-600 transition pulse-glow">
      <span class="font-bold">🚀 Começar Agora</span>
    </a>
  </div>

  <!-- HERO -->
  <header class="gradient-hero text-white py-20 relative overflow-hidden">
    <div class="container mx-auto max-w-7xl px-6 lg:px-8 text-center relative z-10">
      <div class="fade-in">
        <div class="inline-block mb-4 px-4 py-2 bg-emerald-500/20 backdrop-blur-sm rounded-full border border-emerald-400/30">
          <span class="text-emerald-300 font-semibold">✨ Automação inteligente com IA</span>
        </div>
      </div>
      
      <h1 class="text-4xl md:text-6xl font-black leading-tight mb-6 slide-up">
        Venda <span class="gradient-text">100% no Automático</span> pelo WhatsApp<br>
        <span class="text-white/90 text-3xl md:text-5xl">sem precisar responder ninguém</span>
      </h1>
      
      <p class="text-lg md:text-xl text-white/90 max-w-3xl mx-auto mb-10 slide-up" style="animation-delay: 0.2s">
        Transforme o seu WhatsApp em um vendedor que trabalha 24h por dia.<br>
        Crie assistentes de IA que vendem, atendem e agendam — tudo no automático.
      </p>

      <!-- VIDEO -->
      <div class="max-w-4xl mx-auto aspect-video rounded-2xl overflow-hidden shadow-2xl border border-white/20 slide-up" style="animation-delay: 0.4s">
        <iframe class="w-full h-full" src="https://www.youtube.com/embed/alsj-l6fL80" 
          title="FacilitAI - Vendas automáticas pelo WhatsApp" frameborder="0" allowfullscreen></iframe>
      </div>

      <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center slide-up" style="animation-delay: 0.6s">
        <a href="#planos" class="inline-flex justify-center items-center px-8 py-4 rounded-2xl bg-emerald-400 text-emerald-900 font-bold text-lg hover:scale-105 transition shadow-xl">
          Escolher meu plano
        </a>
        <a href="#como-funciona" class="inline-flex justify-center items-center px-8 py-4 rounded-2xl bg-white/10 backdrop-blur-sm text-white border-2 border-white/30 font-bold text-lg hover:bg-white/20 transition">
          Ver como funciona
        </a>
      </div>
    </div>
  </header>

  <!-- RESULTADOS -->
  <section class="py-20 bg-gradient-to-b from-indigo-900 to-purple-900 text-white">
    <div class="container mx-auto max-w-6xl px-6 lg:px-8">
      <div class="grid md:grid-cols-3 gap-8 text-center">
        <div class="fade-in">
          <div class="stat-number gradient-text">24/7</div>
          <p class="text-xl font-semibold mt-3">Atendimento Contínuo</p>
          <p class="text-white/70 mt-2">Seu assistente nunca dorme</p>
        </div>
        <div class="fade-in" style="animation-delay: 0.2s">
          <div class="stat-number gradient-text">15h</div>
          <p class="text-xl font-semibold mt-3">Economizadas por Semana</p>
          <p class="text-white/70 mt-2">Foque no que realmente importa</p>
        </div>
        <div class="fade-in" style="animation-delay: 0.4s">
          <div class="stat-number gradient-text">∞</div>
          <p class="text-xl font-semibold mt-3">Clientes Simultâneos</p>
          <p class="text-white/70 mt-2">Sem limite de atendimentos</p>
        </div>
      </div>
    </div>
  </section>

  <!-- COMO FUNCIONA -->
  <section id="como-funciona" class="py-24 bg-white">
    <div class="container mx-auto max-w-6xl px-6 lg:px-8 text-center">
      <h2 class="text-4xl font-black text-gray-900 mb-6">Como funciona o FacilitAI</h2>
      <p class="text-lg text-gray-600 max-w-3xl mx-auto mb-16">
        Em poucos minutos você cria um assistente de IA conectado ao seu WhatsApp.  
        Ele responde clientes, envia catálogos, vídeos, PDFs, agenda horários e faz vendas — tudo sem que você precise tocar no celular.
      </p>

      <div class="grid md:grid-cols-3 gap-10">
        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-3xl p-8 border-2 border-indigo-100 card-hover">
          <div class="text-5xl mb-4">🤖</div>
          <h3 class="text-xl font-bold text-indigo-900 mb-3">1️⃣ Crie seu assistente</h3>
          <p class="text-slate-600">Monte um assistente de IA com a personalidade e funções que desejar. Sem código, em minutos.</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-3xl p-8 border-2 border-emerald-100 card-hover">
          <div class="text-5xl mb-4">📱</div>
          <h3 class="text-xl font-bold text-emerald-900 mb-3">2️⃣ Conecte ao WhatsApp</h3>
          <p class="text-slate-600">Conecte sua conta e veja o assistente responder mensagens, enviar vídeos e PDFs automaticamente.</p>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-3xl p-8 border-2 border-purple-100 card-hover">
          <div class="text-5xl mb-4">💰</div>
          <h3 class="text-xl font-bold text-purple-900 mb-3">3️⃣ Venda no automático</h3>
          <p class="text-slate-600">O assistente faz o trabalho: responde, envia propostas, agenda horários e fecha vendas 24h por dia.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- COMPARAÇÃO -->
  <section class="py-24 bg-gradient-to-b from-slate-50 to-white">
    <div class="container mx-auto max-w-5xl px-6 lg:px-8">
      <h2 class="text-4xl font-black text-gray-900 text-center mb-4">Por que FacilitAI?</h2>
      <p class="text-lg text-gray-600 text-center mb-12">Compare com as alternativas tradicionais</p>
      
      <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-200">
        <table class="w-full comparison-table">
          <thead class="bg-gradient-to-r from-indigo-900 to-purple-900 text-white">
            <tr>
              <th class="text-left px-6 py-4">Recurso</th>
              <th class="px-4 py-4">Atendente Humano</th>
              <th class="px-4 py-4 bg-emerald-600">FacilitAI</th>
            </tr>
          </thead>
          <tbody class="text-slate-700">
            <tr class="border-b border-slate-100">
              <td class="text-left px-6 py-4 font-semibold">Custo mensal</td>
              <td class="text-slate-500">R$ 2.000 - R$ 4.000</td>
              <td class="text-emerald-600 font-bold">A partir de R$ 167</td>
            </tr>
            <tr class="border-b border-slate-100 bg-slate-50">
              <td class="text-left px-6 py-4 font-semibold">Horário de trabalho</td>
              <td class="text-slate-500">8h por dia</td>
              <td class="text-emerald-600 font-bold">24h por dia, 7 dias</td>
            </tr>
            <tr class="border-b border-slate-100">
              <td class="text-left px-6 py-4 font-semibold">Atendimentos simultâneos</td>
              <td class="text-slate-500">1 por vez</td>
              <td class="text-emerald-600 font-bold">Ilimitados</td>
            </tr>
            <tr class="border-b border-slate-100 bg-slate-50">
              <td class="text-left px-6 py-4 font-semibold">Treinamento necessário</td>
              <td class="text-slate-500">Dias ou semanas</td>
              <td class="text-emerald-600 font-bold">Minutos</td>
            </tr>
            <tr>
              <td class="text-left px-6 py-4 font-semibold">Férias/faltas</td>
              <td class="text-slate-500">Precisa substituir</td>
              <td class="text-emerald-600 font-bold">Nunca para</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- BENEFÍCIOS -->
  <section class="py-24 bg-white">
    <div class="container mx-auto max-w-6xl px-6 lg:px-8">
      <h2 class="text-4xl font-black text-gray-900 text-center mb-16">Tudo que você precisa em um só lugar</h2>
      
      <div class="grid md:grid-cols-2 gap-8">
        <div class="flex gap-4 p-6 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 card-hover">
          <div class="text-4xl">📸</div>
          <div>
            <h3 class="font-bold text-lg text-indigo-900 mb-2">Envio de Mídias</h3>
            <p class="text-slate-600">Imagens, vídeos, PDFs e áudios automaticamente para seus clientes</p>
          </div>
        </div>
        
        <div class="flex gap-4 p-6 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 card-hover">
          <div class="text-4xl">📅</div>
          <div>
            <h3 class="font-bold text-lg text-emerald-900 mb-2">Sistema de Agendamento</h3>
            <p class="text-slate-600">Marque consultas e reuniões automaticamente, sem conflitos</p>
          </div>
        </div>
        
        <div class="flex gap-4 p-6 rounded-2xl bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-100 card-hover">
          <div class="text-4xl">📊</div>
          <div>
            <h3 class="font-bold text-lg text-purple-900 mb-2">Disparos em Massa</h3>
            <p class="text-slate-600">Envie mensagens para milhares de leads sem limitações</p>
          </div>
        </div>
        
        <div class="flex gap-4 p-6 rounded-2xl bg-gradient-to-br from-orange-50 to-red-50 border border-orange-100 card-hover">
          <div class="text-4xl">🧠</div>
          <div>
            <h3 class="font-bold text-lg text-orange-900 mb-2">IA Personalizada</h3>
            <p class="text-slate-600">Configure a personalidade e respostas do seu assistente</p>
          </div>
        </div>
        
        <div class="flex gap-4 p-6 rounded-2xl bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100 card-hover">
          <div class="text-4xl">💬</div>
          <div>
            <h3 class="font-bold text-lg text-green-900 mb-2">Sem Limites</h3>
            <p class="text-slate-600">Mensagens, leads e conversas ilimitadas em todos os planos</p>
          </div>
        </div>
        
        <div class="flex gap-4 p-6 rounded-2xl bg-gradient-to-br from-yellow-50 to-amber-50 border border-yellow-100 card-hover">
          <div class="text-4xl">🛡️</div>
          <div>
            <h3 class="font-bold text-lg text-amber-900 mb-2">Suporte Dedicado</h3>
            <p class="text-slate-600">Atendimento direto via WhatsApp quando você precisar</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PLANOS -->
  <section id="planos" class="py-24 bg-slate-100" x-data="{ plano: 'mensal' }">
    <div class="container mx-auto max-w-6xl px-6 lg:px-8 text-center">
      <h2 class="text-4xl font-black text-gray-900 mb-4">Escolha seu plano</h2>
      <p class="text-lg text-gray-600 mb-12">
        Assine o FacilitAI e transforme seu WhatsApp em uma máquina de vendas automática.
      </p>

      
      <!-- TOGGLE -->
      <div class="flex justify-center mb-10 relative">
        <div class="flex bg-white rounded-full shadow-inner border border-slate-200 overflow-hidden">
          <button @click="plano='mensal'" :class="plano==='mensal'?'bg-emerald-400 text-emerald-900':'text-slate-600 hover:bg-slate-100'" 
            class="px-6 py-2 font-semibold transition-all">Plano Mensal</button>
          <button @click="plano='anual'" :class="plano==='anual'?'bg-emerald-400 text-emerald-900':'text-slate-600 hover:bg-slate-100'" 
            class="px-6 py-2 font-semibold transition-all">
            Plano Anual
            
          </button>
        </div>
        
        <!-- DESCONTO -->
        <span class="absolute -top-2 right-0 md:right-[400px] bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-lg">
            -25%
        </span>
      </div>

      <div class="grid md:grid-cols-2 gap-8">
        <!-- PLANO INDIVIDUAL -->
        <div class="bg-white rounded-3xl p-10 border-2 border-slate-200 shadow-lg card-hover">
          <h3 class="text-2xl font-bold text-indigo-900 mb-2">Plano Individual</h3>
          <p class="text-slate-600 mb-6">Ideal para quem quer começar e testar o poder da automação com IA.</p>
          <ul class="text-left text-slate-700 space-y-3 mb-8">
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>1 conexão de WhatsApp</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>1 assistente de IA</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>1 sistema de agendamento</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Suporte por WhatsApp</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Envio de imagens, vídeos, PDFs e áudios</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Disparo em massa ilimitado</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Sem limite de leads</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Sem limite de Mensagens</span></li>
            <li class="flex items-start gap-2"><span class="text-blue-500 text-xl">💠</span> <span>Tokens ilimitados direto da OpenAI (você paga apenas centavos por uso)</span></li>
          </ul>

          <!-- PREÇOS INDIVIDUAL -->
          <div x-cloak x-show="plano === 'mensal'">
            <div class="text-4xl font-black text-indigo-700 mb-2">R$197<span class="text-2xl text-slate-500">/mês</span></div>
            <p class="text-slate-500 mb-6">Cobrança mensal</p>
            <a href="https://pay.hotmart.com/U102725550Y?off=yqncr3mx"
               class="block w-full px-8 py-4 rounded-2xl bg-indigo-600 text-white font-bold text-lg hover:bg-indigo-700 hover:scale-105 transition shadow-lg">
               Assinar Plano Mensal
            </a>
          </div>

          <div x-cloak x-show="plano === 'anual'">
            <div class="text-4xl font-black text-indigo-700 mb-2">R$167<span class="text-2xl text-slate-500">/mês</span></div>
            <p class="text-slate-500 mb-2">Cobrança anual (R$2.004/ano)</p>
            <p class="text-emerald-600 font-semibold mb-4">💰 Economize R$360 por ano</p>
            <a href="https://pay.hotmart.com/U102725550Y?off=kemggz0j"
               class="block w-full px-8 py-4 rounded-2xl bg-indigo-600 text-white font-bold text-lg hover:bg-indigo-700 hover:scale-105 transition shadow-lg">
               Assinar Plano Anual
            </a>
          </div>
        </div>

        <!-- PLANO PROFISSIONAL -->
        <div class="bg-white rounded-3xl p-10 border-4 border-emerald-400 shadow-2xl relative card-hover">
          <div class="absolute -top-4 right-6 bg-gradient-to-r from-emerald-400 to-teal-400 text-white text-sm font-bold px-6 py-2 rounded-full shadow-lg">
            ⭐ MAIS POPULAR
          </div>
          <h3 class="text-2xl font-bold text-emerald-700 mb-2">Plano Profissional</h3>
          <p class="text-slate-600 mb-6">Perfeito para quem quer escalar suas vendas e gerenciar vários números.</p>
          <ul class="text-left text-slate-700 space-y-3 mb-8">
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Até 3 conexões de WhatsApp</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Até 3 assistentes de IA</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Até 3 sistemas de agendamento</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Suporte por WhatsApp</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Envio de imagens, vídeos, PDFs e áudios</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Disparo em massa ilimitado</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Sem limite de leads</span></li>
            <li class="flex items-start gap-2"><span class="text-emerald-500 text-xl">✓</span> <span>Sem limite de Mensagens</span></li>
            <li class="flex items-start gap-2"><span class="text-blue-500 text-xl">💠</span> <span>Tokens ilimitados direto da OpenAI (pague apenas pelo consumo)</span></li>
          </ul>

          <!-- PREÇOS PROFISSIONAL -->
          <div x-cloak x-show="plano === 'mensal'">
            <div class="text-4xl font-black text-emerald-700 mb-2">R$397<span class="text-2xl text-slate-500">/mês</span></div>
            <p class="text-slate-500 mb-6">Cobrança mensal</p>
            <a href="https://pay.hotmart.com/U102725550Y?off=x8jw71pc"
               class="block w-full px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold text-lg hover:from-emerald-600 hover:to-teal-600 hover:scale-105 transition shadow-lg">
               Assinar Plano Mensal
            </a>
          </div>

          <div x-cloak x-show="plano === 'anual'">
            <div class="text-4xl font-black text-emerald-700 mb-2">R$297<span class="text-2xl text-slate-500">/mês</span></div>
            <p class="text-slate-500 mb-2">Cobrança anual (R$3.564/ano)</p>
            <p class="text-emerald-600 font-semibold mb-4">💰 Economize R$1.200 por ano</p>
            <a href="https://pay.hotmart.com/U102725550Y?off=kbejejiv"
               class="block w-full px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold text-lg hover:from-emerald-600 hover:to-teal-600 hover:scale-105 transition shadow-lg">
               Assinar Plano Anual
            </a>
          </div>
        </div>
      </div>

      <!-- GARANTIA -->
      <div class="mt-16 bg-gradient-to-r from-indigo-900 to-purple-900 rounded-3xl p-10 text-white shadow-xl">
        <div class="flex flex-col md:flex-row items-center justify-center gap-6">
          <div class="text-6xl">🛡️</div>
          <div class="text-center md:text-left">
            <h3 class="text-2xl font-bold mb-2">Garantia de Satisfação</h3>
            <p class="text-white/90">Cancele quando quiser, sem burocracia. Sem multas ou taxas escondidas.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
    <section class="py-24 bg-white">
        <div class="container mx-auto max-w-5xl px-6 lg:px-8">
            <h2 class="text-4xl font-black text-gray-900 text-center mb-12">Perguntas frequentes</h2>
            <div class="space-y-6">
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-indigo-300 transition">
                <h3 class="font-bold text-lg text-indigo-900 mb-2">❓ Preciso saber programar?</h3>
                <p>Não! O FacilitAI foi feito para qualquer pessoa usar. Tudo é visual e intuitivo — basta preencher campos e escolher opções.</p>
                </div>
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-indigo-300 transition">
                    <h3 class="font-bold text-lg text-indigo-900 mb-2">❓ O que significa "tokens ilimitados"?</h3>
                    <p>Agora os tokens são pagos direto pela sua conta da OpenAI. Isso elimina limitações — você usa o quanto quiser e paga apenas centavos de dólar por milhão de tokens, tornando o custo super acessível e escalável.</p>
                </div>
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-indigo-300 transition">
                    <h3 class="font-bold text-lg text-indigo-900 mb-2">❓ Posso mudar de plano depois?</h3>
                    <p>Sim! Você pode começar no plano Individual e migrar para o Profissional a qualquer momento, de acordo com as suas necessidades e o crescimento do seu negócio.</p>
                </div>
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-indigo-300 transition">
                    <h3 class="font-bold text-lg text-indigo-900 mb-2">❓ Tem suporte?</h3>
                    <p>Sim, oferecemos suporte direto e rápido via WhatsApp para tirar todas as suas dúvidas e ajudar na configuração e otimização do seu assistente de IA.</p>
                </div>
                <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 hover:border-indigo-300 transition">
                    <h3 class="font-bold text-lg text-indigo-900 mb-2">❓ O que acontece se eu cancelar?</h3>
                    <p>Você pode cancelar a qualquer momento, sem burocracia ou multas. Sua conta permanecerá ativa até o final do período já pago.</p>
                </div>
            </div>
            <div class="text-center mt-16">
                <a href="#planos" class="inline-flex justify-center items-center px-10 py-5 rounded-2xl bg-emerald-400 text-emerald-900 font-bold text-lg hover:scale-105 transition shadow-xl">
                Escolher meu plano agora
                    </a>
            </div>
        </div>
    </section>
<!-- CALL TO ACTION FINAL -->

<section class="py-20 bg-indigo-900 text-white text-center">
<div class="container mx-auto max-w-4xl px-6 lg:px-8">
<h2 class="text-4xl font-black mb-6">Pronto para transformar seu WhatsApp?</h2>
<p class="text-xl text-white/80 mb-10">
Junte-se a centenas de negócios que estão vendendo mais e trabalhando menos com o FacilitAI.
</p>
<a href="#planos" class="inline-flex justify-center items-center px-12 py-6 rounded-2xl bg-emerald-400 text-emerald-900 font-bold text-xl hover:scale-105 transition shadow-2xl pulse-glow">
Começar a vender no automático!
</a>
</div>
</section>

<!-- FOOTER -->
 @include('homepage.footer') 


</body>
</html>
