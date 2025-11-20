<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Workshop IA Lucrativa — Crie e Venda Assistentes de IA</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');
    body { font-family: 'Inter', sans-serif; }

    .gradient-hero {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 25%, #4c1d95 50%, #5b21b6 75%, #6366f1 100%);
      position: relative;
    }
    .gradient-hero::before {
      content: '';
      position: absolute; inset: 0;
      background: radial-gradient(circle at 20% 50%, rgba(139,92,246,.3) 0%, transparent 50%),
                  radial-gradient(circle at 80% 80%, rgba(99,102,241,.3) 0%, transparent 50%);
      animation: pulse 8s ease-in-out infinite;
    }
    @keyframes pulse { 0%,100%{opacity:.5;} 50%{opacity:.8;} }

    .btn-primary {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 10px 30px rgba(16,185,129,0.3);
      transition: all 0.3s ease;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(16,185,129,0.4); }
    .btn-secondary { backdrop-filter: blur(10px); transition: all 0.3s ease; }
    .btn-secondary:hover { background: rgba(255,255,255,.2); transform: translateY(-2px); }

    .card-hover { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .card-hover:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }

    .glass-card {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.2);
    }
    .gradient-text {
      background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  </style>
</head>

<body class="antialiased text-gray-800 bg-slate-50">

  <!-- HERO -->
  <header id="topo" class="relative gradient-hero text-white overflow-hidden min-h-screen flex items-center">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8 py-20 relative z-10">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

        <div class="space-y-8">
          <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 rounded-full px-5 py-2 text-sm backdrop-blur-sm">
            <span class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
            </span>
            <span class="font-medium">Ao vivo no Zoom • Sábado, dia 25 de outubro às 8h–12h</span>
          </div>

          <h1 class="text-4xl md:text-6xl font-black leading-tight">
            Workshop IA Lucrativa: <span class="gradient-text">Como Criar e Vender Assistentes de IA</span> que Fecham Contratos de R$1.500 a R$5.000 por Cliente
          </h1>

          <p class="text-xl md:text-2xl text-white/90 leading-relaxed">
            Um treinamento 100% ao vivo e prático para quem quer <span class="font-bold text-emerald-300">revender</span> assistentes de IA como serviço — sem precisar programar.
          </p>

          <div class="flex flex-col sm:flex-row gap-4 pt-4">
            <a href="#planos" class="btn-primary inline-flex justify-center items-center px-8 py-5 rounded-2xl text-white font-bold text-lg">
              Garantir meu ingresso
            </a>
            <a href="#detalhes" class="btn-secondary inline-flex justify-center items-center px-8 py-5 rounded-2xl border-2 border-white/30 text-white font-semibold text-lg">
              Ver o que vou aprender
            </a>
          </div>
        </div>

        <!-- CARTÃO INSTRUTOR -->
        <div class="relative">
          <div class="glass-card rounded-3xl p-8 shadow-2xl">
            <div class="flex flex-col md:flex-row items-center gap-6 mb-8">
              <img src="{{ asset('storage/workshop/bruno3.webp') }}"
                   alt="Bruno Sampaio"
                   class="w-32 h-32 rounded-full border-4 border-emerald-300 object-cover shadow-lg">
              <div class="text-center md:text-left">
                <p class="text-white/70 text-sm mb-1 uppercase tracking-wider">Ministrado por</p>
                <h3 class="text-3xl font-black text-white mb-2">Bruno Sampaio</h3>
                <p class="text-white/90 leading-relaxed">
                  Programador há mais de <b class="text-emerald-300">12 anos</b> e especialista em vendas. Criador do <b>FacilitAI</b> e fundador do <b>Portal Jovem Empreendedor</b>.
                </p>
              </div>
            </div>

            <ul class="space-y-3 mb-8 text-white/90">
              <li>✅ Modelo de negócio validado para agências</li>
              <li>✅ Oferta e precificação para tickets de R$1K–R$5k</li>
              <li>✅ Demonstração prática: criando um assistente do zero</li>
            </ul>

            <a href="#planos" class="w-full inline-flex justify-center items-center px-8 py-5 rounded-2xl bg-white text-indigo-900 font-bold text-lg hover:bg-emerald-50 transition-all hover:scale-105">
              Escolher meu plano
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- DETALHES -->
  <section id="detalhes" class="py-24 -mt-20 relative z-20">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8">
      <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100">
          <h3 class="text-indigo-700 font-bold text-sm uppercase tracking-wider mb-2">Quando</h3>
          <div class="text-3xl font-black mb-3 text-gray-900">Sábado • 25 de outubro • 8h–12h</div>
          <p class="text-slate-600">Imersão direta ao ponto, com demonstrações reais e estratégia comercial.</p>
        </div>
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100">
          <h3 class="text-emerald-700 font-bold text-sm uppercase tracking-wider mb-2">Para quem é</h3>
          <div class="text-3xl font-black mb-3 text-gray-900">Para pessoas comuns que querem começar algo novo</div>
          <p class="text-slate-600">Esse workshop é para <b>quem quer criar uma nova fonte de renda</b> usando a inteligência artificial — mesmo sem experiência, sem saber programar e partindo do zero.  </p>
        </div>
        <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100">
          <h3 class="text-violet-700 font-bold text-sm uppercase tracking-wider mb-2">Formato</h3>
          <div class="text-3xl font-black mb-3 text-gray-900">Ao vivo no Zoom</div>
          <p class="text-slate-600">Receba o link após a inscrição e participe da imersão completa.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- O QUE VAI APRENDER -->
  <section class="py-24 bg-gradient-to-br from-slate-50 to-indigo-50 text-center">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8">
      <div class="inline-block px-4 py-2 bg-indigo-100 text-indigo-700 rounded-full text-sm font-bold mb-4">O QUE VOCÊ VAI APRENDER</div>
      <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-6">
        Em uma manhã, aprenda como transformar IA em <span class="gradient-text">faturamento real</span>
      </h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed mb-16">
        Você vai sair do evento com clareza sobre o <b>modelo de negócio</b>, a <b>oferta</b>, a <b>entrega</b> e como colocar tudo em prática.
      </p>

      <div class="grid md:grid-cols-3 gap-8 text-left">
        <div class="rounded-3xl p-8 bg-white shadow-xl border border-indigo-100 card-hover">
          <h3 class="font-black text-2xl mb-3 text-gray-900">Criação sob demanda</h3>
          <p class="text-slate-600">Como criar e personalizar assistentes de IA para diferentes nichos.</p>
        </div>
        <div class="rounded-3xl p-8 bg-white shadow-xl border border-emerald-100 card-hover">
          <h3 class="font-black text-2xl mb-3 text-gray-900">Oferta que vende</h3>
          <p class="text-slate-600">Como posicionar e vender com tickets de R$1.500 a R$5.000 por cliente.</p>
        </div>
        <div class="rounded-3xl p-8 bg-white shadow-xl border border-violet-100 card-hover">
          <h3 class="font-black text-2xl mb-3 text-gray-900">Entrega rápida</h3>
          <p class="text-slate-600">Veja ao vivo como configurar tudo e entregar valor em minutos.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- PLANOS -->
  <section id="planos" class="py-24 bg-white">
    <div class="container max-w-6xl mx-auto px-6 lg:px-8 text-center">
      <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">Escolha seu plano de acesso</h2>
      <p class="text-lg text-gray-600 mb-12">Garanta sua vaga e tenha acesso ao Workshop IA Lucrativa</p>

      <div class="grid md:grid-cols-2 gap-8">
        <!-- PLANO 67 -->
        <div class="bg-slate-50 border border-slate-200 rounded-3xl p-10 shadow-sm hover:shadow-lg transition-all card-hover">
          <h3 class="text-2xl font-bold text-indigo-900 mb-2">Plano Essencial</h3>
          <p class="text-slate-600 mb-6">Ideal para quem quer participar do evento ao vivo</p>
          <div class="text-5xl font-extrabold text-indigo-800 mb-6">R$27</div>
          <ul class="text-slate-700 text-left space-y-3 mb-8">
            <li>✅ Acesso ao Workshop ao vivo (Zoom)</li>
            <li>🚫 Sem acesso à gravação</li>
            <li>🚫 Sem modelos ou contratos adicionais</li>
          </ul>
          <a href="{{$planoBasico}}" target="_blank"
             class="btn-primary w-full inline-flex justify-center items-center px-8 py-4 rounded-2xl text-white font-bold text-lg hover:scale-105">
             Garantir Plano R$27
          </a>
        </div>

        <!-- PLANO 87 -->
        <div class="bg-white border-4 border-emerald-400 rounded-3xl p-10 shadow-xl relative card-hover">
          <div class="absolute -top-4 right-6 bg-emerald-400 text-white text-xs font-bold px-4 py-1 rounded-full shadow-md">MAIS VANTAJOSO</div>
          <h3 class="text-2xl font-bold text-emerald-700 mb-2">Plano Premium</h3>
          <p class="text-slate-600 mb-6">Inclui materiais e modelos exclusivos</p>
          <div class="text-5xl font-extrabold text-emerald-600 mb-6">R$47</div>
          <ul class="text-slate-700 text-left space-y-3 mb-8">
            <li>✅ Tudo do Plano Essencial</li>
            <li>✅ Acesso à gravação completa do Workshop</li>
            <li>✅ Modelo de Contrato para venda de Assistentes de IA</li>
            <li>✅ Modelos de Prompts de Assistentes prontos</li>
            <li>✅ Modelos de páginas de captação para:
              <ul class="ml-5 list-disc">
                <li>Clínicas Odontológicas</li>
                <li>Escritórios de Advocacia</li>
                <li>Personal Trainers</li>
              </ul>
            </li>
          </ul>
          <a href="{{$planoPro}}" target="_blank"
             class="btn-primary w-full inline-flex justify-center items-center px-8 py-4 rounded-2xl text-white font-bold text-lg hover:scale-105">
             Quero o Plano Premium R$47
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-10 text-center text-slate-500 text-sm bg-slate-50">
    © 2025 FacilitAI • Todos os direitos reservados
  </footer>




</body>
</html>
