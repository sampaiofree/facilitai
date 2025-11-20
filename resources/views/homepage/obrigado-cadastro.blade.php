<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Realizado - FacilitAI</title>
    <style>
        :root {
            /* Cores */
            --primary-purple: #9333EA;
            --primary-blue: #38BDF8;
            --success-green: #22C55E;
            --success-green-dark: #16A34A;
            --warning-orange: #F59E0B;
            --warning-bg-light: #FEF3C7;
            --warning-text-dark: #92400E;
            --dark-text: #1E293B;
            --medium-text: #475569;
            --light-text: #64748B;
            --white: #FFFFFF;
            --light-bg-gray: #F8FAFC;
            --light-blue-bg: #E0E7FF;
            --border-light: #E2E8F0;

            /* Sombras */
            --shadow-lg: 0 20px 80px rgba(0, 0, 0, 0.3);
            --shadow-md-success: 0 8px 32px rgba(34, 197, 94, 0.3);
            --shadow-hover-success: 0 12px 32px rgba(34, 197, 94, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #9333EA 0%, #38BDF8 100%);
            color: var(--dark-text);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-y: auto;  /* 🔹 Agora permite rolagem */
        }

        /* Somente centraliza no desktop */
        @media (min-width: 769px) {
            body {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }


        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="60" height="60" xmlns="http://www.w3.org/2000/svg"><circle cx="30" cy="30" r="2" fill="white" opacity="0.1"/></svg>');
            animation: float 20s infinite linear;
        }

        @keyframes float {
            from { transform: translateY(0); }
            to { transform: translateY(-60px); }
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: var(--white);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            padding: 60px 50px;
            text-align: center;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--success-green), var(--success-green-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease-out 0.2s both;
            box-shadow: var(--shadow-md-success);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-icon::before {
            content: '✓';
            font-size: 3.5rem;
            color: var(--white);
            font-weight: bold;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark-text);
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .emoji {
            display: inline-block;
            animation: bounce 1s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .subtitle {
            font-size: 1.2rem;
            color: var(--medium-text);
            margin-bottom: 50px;
            line-height: 1.6;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--light-bg-gray) 0%, var(--light-blue-bg) 100%);
            border-radius: 20px;
            padding: 40px;
            margin: 40px 0;
            border: 3px solid var(--primary-purple);
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(var(--primary-purple), 0.1), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .cta-section-content {
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-purple);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .arrow-down {
            font-size: 2rem;
            animation: bounceDown 1.5s ease-in-out infinite;
        }

        @keyframes bounceDown {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(8px); }
        }

        .benefits-list {
            text-align: left;
            margin: 30px 0;
            display: inline-block; /* Para centralizar a lista quando ela for menor que o container */
        }

        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 1.1rem;
            color: var(--dark-text);
        }

        .benefit-icon {
            font-size: 1.4rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .warning-box {
            background: var(--warning-bg-light);
            border-left: 4px solid var(--warning-orange);
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            display: flex;
            align-items: center;
            gap: 16px;
            text-align: left;
        }

        .warning-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }

        .warning-text {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--warning-text-dark);
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--success-green);
            color: var(--white);
            padding: 20px 50px;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md-success);
            border: none;
            cursor: pointer;
            margin: 20px 0;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover-success);
            background: var(--success-green-dark);
        }

        .whatsapp-icon {
            font-size: 1.5rem;
        }

        .disclaimer {
            font-size: 0.95rem;
            color: var(--light-text);
            margin-top: 30px;
            padding: 20px;
            background: var(--light-bg-gray);
            border-radius: 10px;
            font-style: italic;
        }

        .footer-logo {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid var(--border-light);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-purple), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 25px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .subtitle {
                font-size: 1.05rem;
            }

            .cta-title {
                font-size: 1.4rem;
                flex-direction: column;
            }

            .cta-section {
                padding: 30px 20px;
            }

            .benefit-item {
                font-size: 1rem;
            }

            .cta-button {
                padding: 18px 40px;
                font-size: 1.1rem;
                width: 100%; /* Ocupa a largura total em telas menores */
            }
        }

        .pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon" role="img" aria-label="Ícone de sucesso com um visto"></div>
        
        <h1><span class="emoji" role="img" aria-label="Confete de festa">🎉</span> Cadastro realizado com sucesso!</h1>
        
        <p class="subtitle">
            Obrigado por se cadastrar. Assim que a aula gratuita estiver disponível, vamos te avisar diretamente no seu WhatsApp.
        </p>

        <section class="cta-section">
            <div class="cta-section-content">
                <h2 class="cta-title">
                    <span class="arrow-down" role="img" aria-label="Seta para baixo">👇</span>
                    Entre agora no nosso Grupo VIP do WhatsApp
                </h2>
                
                <ul class="benefits-list">
                    <li class="benefit-item">
                        <span class="benefit-icon" role="img" aria-label="Visto verde">✅</span>
                        <span><strong>Aviso em primeira mão</strong> sobre a aula gratuita</span>
                    </li>
                    <li class="benefit-item">
                        <span class="benefit-icon" role="img" aria-label="Visto verde">✅</span>
                        <span><strong>Conteúdos exclusivos</strong> sobre criação de assistentes de IA</span>
                    </li>
                    <li class="benefit-item">
                        <span class="benefit-icon" role="img" aria-label="Visto verde">✅</span>
                        <span><strong>Dicas práticas</strong> para conquistar seus primeiros clientes</span>
                    </li>
                </ul>

                <div class="warning-box" role="alert">
                    <span class="warning-icon" role="img" aria-label="Sinal de alerta">⚠️</span>
                    <span class="warning-text">As vagas no grupo são limitadas — garanta a sua agora mesmo!</span>
                </div>

                <a href="https://chat.whatsapp.com/KLZIFZmZDd19E9ff4cD7tY" class="cta-button pulse" role="button" aria-label="Entrar no Grupo do WhatsApp">
                    <span class="whatsapp-icon" role="img" aria-label="Ícone do WhatsApp">💬</span>
                    Entrar no Grupo do WhatsApp
                </a>

                <p class="disclaimer">
                    Para receber o aviso, é necessário entrar no grupo. Ele é fechado e usado apenas para avisos importantes — sem conversas ou spam.
                </p>
            </div>
        </section>

         @include('homepage.footer') 

    </div>

    <script>
        // Adicionar confetti effect quando a página carregar
        function createConfetti() {
            const colors = ['var(--primary-purple)', 'var(--primary-blue)', 'var(--success-green)', 'var(--warning-orange)'];
            const confettiCount = 50;

            for (let i = 0; i < confettiCount; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.style.position = 'fixed';
                    confetti.style.width = '10px';
                    confetti.style.height = '10px';
                    // Usar getComputedStyle para pegar o valor real da variável CSS
                    confetti.style.backgroundColor = getComputedStyle(document.documentElement).getPropertyValue(colors[Math.floor(Math.random() * colors.length)]);
                    confetti.style.left = Math.random() * window.innerWidth + 'px';
                    confetti.style.top = '-10px';
                    confetti.style.borderRadius = '50%';
                    confetti.style.pointerEvents = 'none';
                    confetti.style.zIndex = '9999';
                    confetti.style.opacity = '0.8';
                    
                    document.body.appendChild(confetti);

                    const duration = 2000 + Math.random() * 1000;
                    const xMovement = (Math.random() - 0.5) * 200;
                    
                    confetti.animate([
                        { 
                            transform: 'translateY(0) translateX(0) rotate(0deg)',
                            opacity: 0.8
                        },
                        { 
                            transform: `translateY(${window.innerHeight + 20}px) translateX(${xMovement}px) rotate(${Math.random() * 360}deg)`,
                            opacity: 0
                        }
                    ], {
                        duration: duration,
                        easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                    }).onfinish = () => confetti.remove();
                }, i * 30);
            }
        }

        // Executar confetti quando a página carregar
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html>