<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizzaria Exótica - Sabores Únicos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            animation: fadeIn 1s ease-in;
        }

        h1 {
            font-size: 3em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .subtitle {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .pizzas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .pizza-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideUp 0.6s ease-out;
        }

        .pizza-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        .pizza-emoji {
            font-size: 4em;
            text-align: center;
            margin-bottom: 15px;
        }

        .pizza-name {
            font-size: 1.8em;
            color: #764ba2;
            margin-bottom: 10px;
            text-align: center;
            font-weight: bold;
        }

        .pizza-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .pizza-ingredients {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            font-size: 0.9em;
            color: #555;
        }

        .ingredients-title {
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 5px;
        }

        .price {
            text-align: center;
            font-size: 1.5em;
            color: #667eea;
            font-weight: bold;
            margin-top: 15px;
        }

        footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            padding: 20px;
            opacity: 0.9;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            h1 { font-size: 2em; }
            .subtitle { font-size: 1em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🍕 Pizzaria Exótica 🍕</h1>
            <p class="subtitle">Ouse experimentar sabores de outro mundo!</p>
        </header>

        <div class="pizzas-grid">
            <div class="pizza-card">
                <div class="pizza-emoji">🌈</div>
                <h2 class="pizza-name">Pizza Arco-Íris Lunar</h2>
                <p class="pizza-description">Uma explosão de cores e sabores que só existe na lua cheia! Massa tingida naturalmente com frutas do dragão.</p>
                <div class="pizza-ingredients">
                    <div class="ingredients-title">Ingredientes:</div>
                    Queijo de nuvem derretido, geleia de estrelas cadentes, pétalas de flores cósmicas, mel de abelhas espaciais
                </div>
                <div class="price">R$ 89,90</div>
            </div>

            <div class="pizza-card">
                <div class="pizza-emoji">🌋</div>
                <h2 class="pizza-name">Pizza Vulcão de Chocolate</h2>
                <p class="pizza-description">Doce e picante ao mesmo tempo! O chocolate derrete como lava quando chega à sua mesa.</p>
                <div class="pizza-ingredients">
                    <div class="ingredients-title">Ingredientes:</div>
                    Chocolate 90% cacau, pimenta fantasma cristalizada, marshmallow de baunilha negra, caramelo salgado de Marte
                </div>
                <div class="price">R$ 79,90</div>
            </div>

            <div class="pizza-card">
                <div class="pizza-emoji">🦑</div>
                <h2 class="pizza-name">Pizza Tentáculo Galáctico</h2>
                <p class="pizza-description">Frutos do mar de dimensões alternativas! Brilha no escuro graças ao plâncton bioluminescente.</p>
                <div class="pizza-ingredients">
                    <div class="ingredients-title">Ingredientes:</div>
                    Lula cósmica, camarões de Netuno, algas das profundezas, molho de tinta intergaláctica, coral em pó
                </div>
                <div class="price">R$ 95,90</div>
            </div>

            <div class="pizza-card">
                <div class="pizza-emoji">🧙‍♂️</div>
                <h2 class="pizza-name">Pizza Poção Mágica</h2>
                <p class="pizza-description">Criada por bruxos gourmets! Muda de sabor a cada mordida através de encantamentos culinários.</p>
                <div class="pizza-ingredients">
                    <div class="ingredients-title">Ingredientes:</div>
                    Cogumelos de floresta encantada, ervas místicas, queijo de cabra metamórfica, cristais de sal rosa do Himalaia mágico
                </div>
                <div class="price">R$ 85,90</div>
            </div>

            <div class="pizza-card">
                <div class="pizza-emoji">🦖</div>
                <h2 class="pizza-name">Pizza Jurássica</h2>
                <p class="pizza-description">Sabores pré-históricos ressuscitados! Feita com ingredientes cultivados de sementes da era dos dinossauros.</p>
                <div class="pizza-ingredients">
                    <div class="ingredients-title">Ingredientes:</div>
                    Folhas de samambaia gigante, ovos de pterodátilos de granja, carne de planta carnívora, molho de frutas jurássicas
                </div>
                <div class="price">R$ 92,90</div>
            </div>

            <div class="pizza-card">
                <div class="pizza-emoji">❄️</div>
                <h2 class="pizza-name">Pizza Gelo & Fogo</h2>
                <p class="pizza-description">Uma batalha épica de temperaturas! Metade congelada a -50°C, metade flamejante a 300°C.</p>
                <div class="pizza-ingredients">
                    <div class="ingredients-title">Ingredientes:</div>
                    Sorvete de nitrogen líquido, pimenta dragon breath, cristais de gelo comestíveis, molho vulcânico, queijo criogênico
                </div>
                <div class="price">R$ 99,90</div>
            </div>
        </div>

        <footer>
            <p>⚠️ Avisos: Alguns ingredientes podem causar teleporte temporário, visão noturna ou euforia inexplicável.</p>
            <p>📞 Entrega interdimensional disponível | 🚀 Aceitamos moedas de todos os planetas</p>
        </footer>
    </div>
</body>
</html>