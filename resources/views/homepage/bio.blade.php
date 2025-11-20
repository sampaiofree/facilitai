<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bruno Sampaio - Links Oficiais</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #6D28D9; /* Roxo FacilitAI */
            --secondary: #3B82F6; /* Azul 3F7 */
            --accent: #10B981; /* Verde destaque */
            --white: #FFFFFF;
            --text-light: #E5E7EB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 2rem 1rem;
            background-attachment: fixed;
        }

        .container {
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        /* Perfil */
        .profile {
            margin-bottom: 2rem;
        }

        .profile-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            background-image: url('{{ asset('storage/workshop/bruno3.webp') }}');
            background-size: cover;
            background-position: center;
            border: 3px solid var(--white);
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .profile h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .profile p {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        /* Links principais */
        .link-card {
            display: block;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.2rem 1rem;
            border-radius: 14px;
            margin-bottom: 1rem;
            color: var(--white);
            position: relative;
            transition: all 0.3s ease;
        }

        .link-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.2);
        }

        .link-card .icon {
            position: absolute;
            right: 15px;
            bottom: 5px;
            font-size: 3rem;
            color: rgba(255,255,255,0.1);
        }

        .link-title {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .link-subtitle {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* Destaque para o principal */
        .highlight {
            background: linear-gradient(90deg, var(--accent), var(--secondary));
            font-weight: 600;
            border: none;
        }

        .highlight:hover {
            background: linear-gradient(90deg, var(--secondary), var(--accent));
        }

        footer {
            margin-top: 2rem;
            font-size: 0.8rem;
            color: var(--text-light);
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Perfil -->
        <header class="profile">
            <div class="profile-img"></div>
            <h1>Bruno Sampaio</h1>
            <p>Aprenda a criar e vender Assistentes de IA no WhatsApp 🚀</p>
        </header>

        <!-- Links -->
        <main>
            <a target="_blank" href="https://chat.whatsapp.com/KLZIFZmZDd19E9ff4cD7tY" class="link-card highlight">
                <span class="icon">💬</span>
                <div class="link-title">Comunidade Gratuita no WhatsApp</div>
                <div class="link-subtitle">Entre para aprender e receber novidades</div>
            </a>

            <a target="_blank" href="https://app.3f7.org/workshop" class="link-card">
                <span class="icon">🎓</span>
                <div class="link-title">Workshop Exclusivo</div>
                <div class="link-subtitle">Aprenda a lucrar com IA no WhatsApp</div>
            </a>

            <a target="_blank" href="https://app.3f7.org/facilitai" class="link-card">
                <span class="icon">🤖</span>
                <div class="link-title">FacilitAI</div>
                <div class="link-subtitle">Crie e venda assistentes de IA</div>
            </a>

            <a target="_blank" href="https://wa.me/5562995772922" class="link-card">
                <span class="icon">📱</span>
                <div class="link-title">Falar com Bruno</div>
                <div class="link-subtitle">Converse comigo no WhatsApp</div>
            </a>
        </main>

         @include('homepage.footer') 

    </div>
</body>
</html>
