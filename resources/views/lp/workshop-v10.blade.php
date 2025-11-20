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
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 20% 50%, rgba(139, 92, 246, 0.3) 0%, transparent 50%),
                  radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.3) 0%, transparent 50%);
      animation: pulse 8s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 0.5; }
      50% { opacity: 0.8; }
    }

    .card-hover { transition: all 0.3s cubic-bezier(0.4,0,0.2,1); }
    .card-hover:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }

    .btn-primary {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 10px 30px rgba(16,185,129,0.3);
      transition: all 0.3s ease;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(16,185,129,0.4); }

    .btn-secondary {
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
    }
    .btn-secondary:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }

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

    .number-badge {
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      box-shadow: 0 4px 15px rgba(99,102,241,0.4);
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
            <span class="font-medium">Ao vivo no Zoom • Sábado, 8h–12h</span>
          </div>

          <h1 class="text-4xl md:text-6xl font-black leading-tight">
            Workshop IA Lucrativa: <span class="gradient-text">Como Criar e Vender Assistentes de IA</span> que Fecham Contratos de R$3.000 a R$10.000 por Cliente
          </h1>

          <p class="text-xl md:text-2xl text-white/90 leading-relaxed">
            Um treinamento 100% ao vivo e prático para quem quer <span class="font-bold text-emerald-300">revender</span> assistentes de IA como serviço — sem precisar programar.
          </p>

          <div class="flex flex-col sm:flex-row gap-4 pt-4">
            <a href="https://pay.hotmart.com/Y102167704K?off=c73qv34u" class="btn-primary inline-flex justify-center items-center px-8 py-5 rounded-2xl text-white font-bold text-lg">
              Garantir meu ingresso — R$10
            </a>
            <a href="#detalhes" class="btn-secondary inline-flex justify-center items-center px-8 py-5 rounded-2xl border-2 border-white/30 text-white font-semibold text-lg">
              Ver o que vou aprender
            </a>
          </div>

          <div class="flex items-center gap-4 text-white/90 pt-2">
            <div class="text-sm">
              <div class="font-semibold">Vagas limitadas</div>
              <div class="text-white/70">Evento com valor simbólico de R$10</div>
            </div>
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
                  Programador há mais de <b class="text-emerald-300">12 anos</b> e especialista em vendas, com mais de <b class="text-emerald-300">R$20 milhões em faturamento</b> nos últimos 5 anos. Criador do <b>FacilitAI</b> e fundador do <b>Portal Jovem Empreendedor</b>.
                </p>
              </div>
            </div>

            <div class="space-y-3 mb-8">
              <div class="flex items-start gap-3 text-white/90">
                <span class="text-emerald-400 text-xl">✔</span>
                <span class="font-medium">Modelo de negócio validado para agências</span>
              </div>
              <div class="flex items-start gap-3 text-white/90">
                <span class="text-emerald-400 text-xl">✔</span>
                <span class="font-medium">Oferta e precificação para tickets de R$3k–R$10k</span>
              </div>
              <div class="flex items-start gap-3 text-white/90">
                <span class="text-emerald-400 text-xl">✔</span>
                <span class="font-medium">Demonstração prática no WhatsApp em minutos</span>
              </div>
            </div>

            <a href="https://pay.hotmart.com/Y102167704K?off=c73qv34u" class="w-full inline-flex justify-center items-center px-8 py-5 rounded-2xl bg-white text-indigo-900 font-bold text-lg hover:bg-emerald-50 transition-all hover:scale-105">
              Participar do Workshop — R$10
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
        <div class="bg-white rounded-3xl p-8 shadow-xl card-hover border border-slate-100">
          <div class="text-indigo-700 font-bold text-sm uppercase tracking-wider mb-2">Quando</div>
          <div class="text-3xl font-black mb-3 text-gray-900">Sábado • 8h–12h</div>
          <p class="text-slate-600 leading-relaxed">Imersão direta ao ponto, focada na prática e no fechamento de contratos.</p>
        </div>

        <div class="bg-white rounded-3xl p-8 shadow-xl card-hover border border-slate-100">
          <div class="text-emerald-700 font-bold text-sm uppercase tracking-wider mb-2">Para quem é</div>
          <div class="text-3xl font-black mb-3 text-gray-900">Agências & Freelancers</div>
          <p class="text-slate-600 leading-relaxed">Profissionais que querem vender Assistentes de IA para empresas (não é para cliente final).</p>
        </div>

        <div class="bg-white rounded-3xl p-8 shadow-xl card-hover border border-slate-100">
          <div class="text-violet-700 font-bold text-sm uppercase tracking-wider mb-2">Formato</div>
          <div class="text-3xl font-black mb-3 text-gray-900">Ao vivo no Zoom</div>
          <p class="text-slate-600 leading-relaxed">Você receberá o link do evento logo após confirmar sua inscrição.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTEÚDO / BENEFÍCIOS -->
  <section class="py-24 bg-gradient-to-br from-slate-50 to-indigo-50">
    <div class="container max-w-7xl mx-auto px-6 lg:px-8 text-center">
      <div class="inline-block px-4 py-2 bg-indigo-100 text-indigo-700 rounded-full text-sm font-bold mb-4">O QUE VOCÊ VAI APRENDER</div>
      <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-6">
        Em uma manhã, entenda como transformar IA em <span class="gradient-text">faturamento real</span>
      </h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed mb-16">
        Você vai sair entendendo o <b>modelo de negócio</b>, a <b>oferta</b>, a <b>entrega</b> e como crias as automações no WhatsApp para rodar em minutos.
      </p>

      <div class="grid md:grid-cols-3 gap-8 text-left">
        <div class="rounded-3xl p-8 bg-white shadow-xl border border-indigo-100 card-hover">
          <h3 class="font-black text-2xl mb-3 text-gray-900">Criação sob demanda</h3>
          <p class="text-slate-600 leading-relaxed">Como montar assistentes de IA sob medida para cada nicho, em minutos.</p>
        </div>
        <div class="rounded-3xl p-8 bg-white shadow-xl border border-emerald-100 card-hover">
          <h3 class="font-black text-2xl mb-3 text-gray-900">Oferta que vende</h3>
          <p class="text-slate-600 leading-relaxed">Como posicionar, apresentar e precificar para tickets de R$3.000 a R$10.000.</p>
        </div>
        <div class="rounded-3xl p-8 bg-white shadow-xl border border-violet-100 card-hover">
          <h3 class="font-black text-2xl mb-3 text-gray-900">Entrega rápida</h3>
          <p class="text-slate-600 leading-relaxed">Demonstração real: criando um assistente do zero durante o evento.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- INSCRIÇÃO -->
  <section id="inscricao" class="py-24 bg-white">
    <div class="container max-w-5xl mx-auto px-6 lg:px-8 text-center">
      <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">Garanta seu ingresso agora</h2>
      <p class="text-lg text-gray-600 mb-8">Valor simbólico de <b class="text-emerald-600 text-xl">R$10</b> — vagas limitadas.</p>

      <a href="https://pay.hotmart.com/Y102167704K?off=c73qv34u" target="_blank"
         class="btn-primary inline-flex justify-center items-center px-10 py-5 rounded-2xl text-white font-bold text-xl hover:scale-105 transition">
        Quero participar por apenas R$10
      </a>

      <p class="text-sm text-gray-500 mt-4">Após a confirmação, você receberá o link do Zoom por e-mail e WhatsApp.</p>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-10 text-center text-slate-500 text-sm bg-slate-50">
    © 2025 FacilitAI • Todos os direitos reservados
  </footer>

</body>
</html>
