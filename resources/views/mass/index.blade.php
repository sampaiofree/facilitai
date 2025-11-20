<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Disparo em Massa') }}
        </h2>
    </x-slot>
    <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="{ usarIA: false }">

        <form method="POST" action="{{ route('mass.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label class="block font-semibold mb-1">Instância</label>
                <select name="instance_id" required class="w-full border-gray-300 rounded-lg">
                    <option value="">Selecione...</option>
                    @foreach($instances as $instance)
                        <option value="{{ $instance->id }}">{{ $instance->name }}</option>
                    @endforeach
                </select>
            </div>

            <input type="hidden" name="tipo_envio" value="texto">

            <div>
                <label class="block font-semibold mb-1">Prompt para Envio</label>
                <textarea name="mensagem" rows="3" class="w-full border-gray-300 rounded-lg" placeholder="Digite sua mensagem de texto..."></textarea>
            </div>

            <!--<div class="flex items-center gap-2">
                <input type="checkbox" id="usar_ia" name="usar_ia" x-model="usarIA" class="rounded border-gray-300">
                <label for="usar_ia" class="font-medium">Usar Inteligência Artificial para gerar as respostas</label>
            </div>-->

            <div>
                <label class="block font-semibold mb-1">Intervalo entre envios (segundos)</label>
                <input type="number" name="intervalo_segundos" min="2" max="900" value="5"
                       class="w-32 border-gray-300 rounded-lg p-2 text-center">
            </div>

            <div>
                <label class="block font-semibold mb-1">Lista de Números (.csv)</label>
                <input type="file" name="arquivo" accept=".csv,.txt" class="w-full border-gray-300 rounded-lg p-2">
                <p class="text-sm text-gray-500 mt-1">Um número por linha. Exemplo: 62999998888</p>
            </div>

            <div class="text-right">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg shadow">
                    Iniciar Disparo
                </button>
            </div>
        </form>
    </div>
    </div>
</x-app-layout>
