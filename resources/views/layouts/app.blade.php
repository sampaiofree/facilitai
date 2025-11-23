<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @stack('head')


        <style>
            .bg-blue-500{background: rgb(71, 0, 202);}
            .bg-blue-600{background: rgb(59, 0, 168);}
            .bg-green-600{background-color: green;}
            .bg-green-700{background-color: rgb(0, 94, 0);}
            .text-blue-600{color: blue;}
            .text-indigo-600{color: indigo;}
        </style>
        @stack('style')
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            
                @include('layouts.navigation')
            
            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                 {{-- ============================================================= --}}
                {{-- BLOCO DE ALERTAS GLOBAIS (COLOQUE AQUI) --}}
                {{-- ============================================================= --}}
                @if (session('success') OR session('error'))
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-6"> {{-- pt-6 para dar um espaço no topo --}}
                        @if (session('success'))
                            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif
                    </div>
                @endif

                @if ($errors->any())
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 pt-6">
                    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg" role="alert">
                        @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
                    </div>
                </div>
                @endif
                {{-- ============================================================= --}}
                {{ $slot }}
                
            </main>
        </div>
        @include('components.lessons.widget')
           <!-- ========== Componente de Alerta Global ========== -->
        <div id="global-alert" class="fixed top-5 right-5 w-full max-w-sm p-4 rounded-lg shadow-lg text-white transform translate-x-[120%] transition-transform duration-500 ease-in-out" style="z-index: 9999;">
            <p id="global-alert-message">Mensagem de teste.</p>
        </div>
        <!-- ================================================= -->
        <script>
            // Função global para exibir alertas customizados
            // tipo pode ser: 'success' (verde), 'error' (vermelho), 'warning' (amarelo), 'info' (azul)
            function showAlert(message, type = 'info') {
                const alertBox = document.getElementById('global-alert');
                const alertMessage = document.getElementById('global-alert-message');

                // Define a mensagem
                alertMessage.innerText = message;

                // Define a cor baseada no tipo
                const colors = {
                    success: 'bg-green-500',
                    error: 'bg-red-500',
                    warning: 'bg-yellow-500',
                    info: 'bg-blue-500'
                };
                
                // Limpa cores antigas e adiciona a nova
                alertBox.classList.remove('bg-green-500', 'bg-red-500', 'bg-yellow-500', 'bg-blue-500');
                alertBox.classList.add(colors[type] || colors['info']);

                // Anima o alerta para dentro da tela
                alertBox.style.transform = 'translateX(0)';

                // Esconde o alerta automaticamente após 5 segundos
                setTimeout(() => {
                    alertBox.style.transform = 'translateX(120%)';
                }, 5000);
            }
        </script>
        

        @stack('scripts')
        
    </body>
</html>
