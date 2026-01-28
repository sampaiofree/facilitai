<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Conexão: {{ $instance->name }}
        </h2>
    </x-slot>

    <div class="py-12" style="padding: 50px">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-gray-900">
                    <form method="POST" action="{{ route('instances.update', $instance) }}"  style="padding: 50px">
                        @csrf
                        @method('PUT')

                        {{-- Campo Nome --}}
                        {{-- <div>
                            <label for="name" class="block font-medium text-sm text-gray-700">Nome da Conexão</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $instance->name) }}" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" required />
                        </div>
                        <hr class="my-6"> --}}


                        {{-- Lógica para Vincular Assistente --}}
                        <h3 class="font-semibold text-lg mb-2">Vincular Assistente ao WhatsApp</h3>
                        <p class="text-sm text-gray-600 mb-4">Selecione sua credencial e depois escolha o assistente que será vinculado a este WhatsApp.</p>

                        {{-- Seletor de Credencial --}}
                        <div style="margin-bottom: 20px;">
                            <label for="credential_selector" class="block font-medium text-sm text-gray-700">1. Selecione a credencial</label>
                            <select id="credential_selector" name="credential_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Selecione a Credencial --</option>
                                @foreach ($credentials as $credential)
                                    <option value="{{ $credential->id  }}" {{ $instance->credential_id == $credential->id ? 'selected' : '' }}>{{ $credential->label}}</option>
                                @endforeach
                                <option value="" {{ is_null($instance->credential_id) ? 'selected' : '' }}>Tokens</option> {{-- Alterado o valor e adicionado 'id' --}}
                            </select>
                        </div>

                        {{-- Seletor de Modelo (Visível somente com credencial selecionada) --}}
                        <div id="model_selector_container" style="margin-bottom: 20px;"> {{-- Escondido por padrão --}}
                            <label for="model_selector" class="block font-medium text-sm text-gray-700">2. Selecione o Modelo do GPT</label>
                            <select id="model_selector" name="model" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Selecione o Modelo --</option>
                                {{-- Modelos GPT-5 --}}
                                <!--<option value="gpt-5" {{ $instance->model == 'gpt-5' ? 'selected' : '' }}>GPT-5</option>
                                <option value="gpt-5-mini" {{ $instance->model == 'gpt-5-mini' ? 'selected' : '' }}>GPT-5-mini</option>
                                <option value="gpt-5-nano" {{ $instance->model == 'gpt-5-nano' ? 'selected' : '' }}>GPT-5-nano</option>
                                <option value="gpt-5-chat-latest" {{ $instance->model == 'gpt-5-chat-latest' ? 'selected' : '' }}>GPT-5-Chat-Latest</option>-->
                               
                               

                                {{-- Modelos GPT-4.1 --}}
                                <option value="gpt-4.1" {{ $instance->model == 'gpt-4.1' ? 'selected' : '' }}>GPT-4.1</option>
                                <option value="gpt-4.1-mini" {{ $instance->model == 'gpt-4.1-mini' ? 'selected' : '' }}>GPT-4.1-mini</option>
                                <option value="gpt-4.1-nano" {{ $instance->model == 'gpt-4.1-nano' ? 'selected' : '' }}>GPT-4.1-nano</option>


                                
                            </select>
                        </div>

                        {{-- Seletor de Assistente --}}
                        <div>
                            <label for="assistant_selector" class="block font-medium text-sm text-gray-700">3. Selecione o assistente</label>
                            <select id="assistant_selector" name="default_assistant_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Selecione o Assistente --</option>
                                @foreach ($assistants as $assistant)
                                    <option value="{{ $assistant->id  }}" {{ $instance->default_assistant_id == $assistant->id ? 'selected' : '' }}>{{ $assistant->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="my-6">

                        {{-- Seletor de Agenda --}}
                        <h3 class="font-semibold text-lg mb-2">Agenda Vinculada</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Escolha qual agenda esta instância deve usar.
                            Se quiser, pode deixar sem agenda.
                        </p>

                        <div>
                            <label for="agenda_id" class="block font-medium text-sm text-gray-700">
                                4. Selecione a agenda (opcional)
                            </label>
                            <select id="agenda_id" name="agenda_id" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Nenhuma agenda --</option>
                                @foreach ($agendas as $agenda)
                                    <option value="{{ $agenda->id }}"
                                        {{ $instance->agenda_id == $agenda->id ? 'selected' : '' }}>
                                        {{ $agenda->titulo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>


                        {{-- Botão Salvar --}}
                        <div class="flex items-center justify-end mt-8">
                            <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const credentialSelector = document.getElementById('credential_selector');
            const modelSelectorContainer = document.getElementById('model_selector_container');
            const modelSelector = document.getElementById('model_selector');
            const assistantSelector = document.getElementById('assistant_selector');

            function toggleModelSelector() {
                const selectedCredentialValue = credentialSelector.value;

                if (selectedCredentialValue && selectedCredentialValue !== '') {
                    // Credencial selecionada (não é 'Tokens')
                    modelSelectorContainer.style.display = 'block'; // Mostra o seletor de modelo
                    modelSelector.removeAttribute('disabled'); // Habilita o seletor
                    // O modelo pode ser qualquer um, mantém o valor atual ou permite seleção
                    modelSelector.required = true; // Torna o campo obrigatório
                } else if (selectedCredentialValue === 'tokens') {
                    // 'Tokens' selecionado
                    modelSelectorContainer.style.display = 'block'; // Ainda mostra, mas com valor fixo
                    modelSelector.value = 'gpt-4.1-mini'; // Define o modelo para gpt-4.1-mini
                    modelSelector.setAttribute('disabled', 'disabled'); // Desabilita o seletor para que o usuário não possa mudar
                    modelSelector.required = true; // Ainda é obrigatório
                } else {
                    // Nenhuma credencial selecionada (opção padrão "-- Selecione a Credencial --")
                    modelSelectorContainer.style.display = 'none'; // Esconde o seletor de modelo
                    modelSelector.value = ''; // Limpa o valor do modelo
                    modelSelector.removeAttribute('disabled'); // Garante que não esteja desabilitado se for reexibido
                    modelSelector.required = false; // Não é obrigatório
                }
            }

            // Chama a função ao carregar a página para definir o estado inicial
            toggleModelSelector();

            // Adiciona um listener para quando o seletor de credenciais mudar
            credentialSelector.addEventListener('change', toggleModelSelector);
        });
    </script>
    @endpush
</x-app-layout>