<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Criar Nova Conexão') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('instances.store') }}">
                        @csrf <!-- Token de segurança do Laravel, obrigatório -->

                        <!-- Nome da Conexão -->
                        <div>
                            <x-input-label for="name" :value="__('Nome da Conexão')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                        </div>

                        <!-- Chave da API da OpenAI -->
                        <div class="mt-4">
                            <x-input-label for="openai_api_key" :value="__('Chave da API da OpenAI')" />
                            <x-text-input id="openai_api_key" class="block mt-1 w-full" type="password" name="openai_api_key" required />
                        </div>
                        
                        <!-- ID do Assistente Padrão -->
                        <div class="mt-4">
                            <x-input-label for="default_assistant_id" :value="__('ID do Assistente OpenAI Padrão (Opcional)')" />
                            <x-text-input id="default_assistant_id" class="block mt-1 w-full" type="text" name="default_assistant_id" :value="old('default_assistant_id')" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('Salvar Conexão') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>