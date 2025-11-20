<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Assistente: <span class="text-indigo-600">{{ $assistant->name }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('assistants.update', $assistant) }}" class="p-8 space-y-6">
                    @csrf
                    @method('PUT') {{-- Informa ao Laravel que esta é uma requisição de atualização --}}

                    <!-- Campo Nome -->
                    <div>
                        <label for="name" class="block font-medium text-sm text-gray-700">Nome do Assistente</label>
                        <input id="name" type="text" name="name" 
                               value="{{ old('name', $assistant->name) }}" 
                               class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required />
                    </div>

                    <!--<div>
                        <label for="credential_id" class="block font-medium text-sm text-gray-700">Credencial</label>
                        <select name="credential_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                
                                @foreach ($credentials as $credential)
                                    <option value="{{ $credential->id }}" @selected($assistant->credential_id == $credential->id)>
                                        {{ $credential->label }} 
                                    </option>
                                @endforeach
                                <option @selected(!$assistant->credential_id) value="">Tokens</option>
                        </select>
                    </div>-->

                                        <div>
                        <label for="delay" class="block font-medium text-sm text-gray-700">Tempo de resposta (segundos)</label>
                        <input id="delay" type="number" name="delay" 
                               value="{{ old('delay', $assistant->delay) }}" 
                               class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" 
                               min="0" placeholder="0 para sem delay" />
                    </div>

                    <!--<div>
                        <label for="modelo" class="block font-medium text-sm text-gray-700">modelo de IA</label>
                        <select id="modelo" name="modelo" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="gpt-5" {{ (old('modelo', $assistant->modelo) == 'gpt-5') ? 'selected' : '' }}>gpt-5</option>
                            <option value="gpt-5-mini" {{ (old('modelo', $assistant->modelo) == 'gpt-5-mini') ? 'selected' : '' }}>gpt-5-mini</option>
                            <option value="gpt-5-nano" {{ (old('modelo', $assistant->modelo) == 'gpt-5-nano') ? 'selected' : '' }}>gpt-5-nano</option>
                            <option value="gpt-4.1-mini" {{ (old('modelo', $assistant->modelo) == 'gpt-4.1-mini') ? 'selected' : '' }}>gpt-4.1-mini</option>

                            @if(Auth::user()->canManageCredentials())
                                <option value="gpt-4.1" {{ (old('modelo', $assistant->modelo) == 'gpt-4.1') ? 'selected' : '' }}>gpt-4.1</option>
                                <option value="gpt-4.1-nano" {{ (old('modelo', $assistant->modelo) == 'gpt-4.1-nano') ? 'selected' : '' }}>gpt-4.1-nano</option>
                            @endif
                            
                            
                        </select>
                    </div>-->

                    <!-- Campo Instruções -->
                    <div>
                        <label for="instructions" class="block font-medium text-sm text-gray-700">Instruções (Prompt)</label>
                        {{-- Usamos a tag <textarea> para preencher com o conteúdo --}}
                        <textarea id="instructions" name="instructions" rows="15" 
                                  class="block mt-1 w-full border-gray-300 rounded-md shadow-sm font-mono text-sm" required>{{ old('instructions', $assistant->instructions) }}</textarea>
                    </div>

                    <!-- Botão de Envio -->
                    <div class="flex items-center justify-end mt-6 border-t pt-6">
                        <a href="{{ route('assistants.index') }}" class="text-gray-600 mr-4">Cancelar</a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>