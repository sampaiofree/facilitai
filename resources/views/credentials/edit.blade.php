<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Credencial: {{ $credential->name }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('credentials.update', $credential) }}">
                        @csrf
                        @method('PUT')
                        <div>
                             <x-input-label for="name" value="Serviço" />

                            {{-- SUBSTITUA O x-text-input POR ESTE BLOCO --}}
                            <select id="name" name="name" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                {{-- A lógica @selected() garante que a opção salva seja a selecionada --}}
                                <option value="OpenAI" @selected(old('name', $credential->name) == 'OpenAI')>OpenAI</option>
                                {{-- <option value="Gemini" @selected(old('name', $credential->name) == 'Gemini')>Gemini</option> --}}
                            </select>
                        </div>
                        {{-- CAMPO RÓTULO NA EDIÇÃO --}}
                        <div class="mt-4">
                            <x-input-label for="label" value="Rótulo (Ex: Chave do Projeto X, Token Pessoal)" />
                            <x-text-input id="label" class="block mt-1 w-full" type="text" name="label" :value="old('label', $credential->label)" required />
                        </div>
                        <div class="mt-4">
                            <x-input-label for="token" value="Novo Token / Chave da API" />
                            <p class="text-sm text-gray-500 mb-1">Preencha apenas se desejar alterar o token existente.</p>
                            <textarea id="token" name="token" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" rows="4">{{$credential->token}}</textarea>
                            <p class="text-sm text-gray-500 mt-1">Token atual: {{ substr($credential->token, 0, 8) }}...</p>
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('Atualizar Credencial') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>