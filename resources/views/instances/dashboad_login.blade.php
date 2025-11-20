<x-public-dashboard-layout>
    {{-- Estilo de fundo gradiente, o mesmo do dashboard --}}
    @push('head')
    <style>
        body {
            background-image: linear-gradient(to bottom right, #111827, #3b0764, #111827);
        }
    </style>
    @endpush

    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md">
            
            <form method="POST" action="{{ route('public.dashboard', $instance->id) }}" 
                  class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 border border-white/20 shadow-xl">
                @csrf
                <h2 class="text-2xl font-bold text-white text-center mb-6">Acessar Dashboard</h2>

                {{-- Exibe a mensagem de erro, se houver --}}
                @if (session('error'))
                    <div class="bg-red-500/30 border border-red-500 text-white text-sm rounded-lg p-3 mb-4 text-center">
                        {{ session('error') }}
                    </div>
                @endif
                
                <div>
                    <label for="instance_name" class="block text-sm font-medium text-gray-300 mb-2">
                        Nome da Conexão
                    </label>
                    <input type="text" id="instance_name" name="instance_name" required autofocus
                           class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all">
                </div>

                <div class="mt-6">
                    <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-xl transition-all">
                        Acessar
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-public-dashboard-layout>