<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-gray-900 leading-tight">
            📅 Minhas Agendas Online
        </h2>
    </x-slot>

    <div class="py-10 bg-gray-50">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Explicação inicial sobre o que são as agendas --}}
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-8 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-800" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Olá! Aqui você pode criar e organizar seus horários de atendimento ou compromissos. Depois de criar, você pode definir os dias e horários que estará disponível.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Seção para criar uma nova agenda --}}
            @if ($availableSlots > 0)
                <div class="p-8 bg-white border border-gray-200 rounded-xl shadow-lg mb-8">
                    <h3 class="text-2xl font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                        ✨ Crie uma Nova Agenda
                    </h3>
                    <p class="text-md text-gray-700 mb-6">
                        Você ainda tem <span class="font-bold text-green-700">{{ $availableSlots }}</span> espaço(s) disponível(is) para novos tipos de agenda. Vamos começar?
                    </p>
                    <form method="POST" action="{{ route('agendas.store') }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="titulo" class="block text-lg font-medium text-gray-800 mb-2">
                                    <span class="text-red-500">*</span> Nome da sua Agenda:
                                </label>
                                <input type="text" id="titulo" name="titulo" placeholder="Ex: Atendimento Terapêutico, Aula de Inglês, Reunião de Equipe"
                                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-3 w-full text-lg @error('titulo') border-red-500 @enderror" required>
                                <p class="text-sm text-gray-500 mt-2">
                                    Dê um nome claro para o tipo de serviço ou compromisso que esta agenda irá gerenciar.
                                </p>
                                @error('titulo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="descricao" class="block text-lg font-medium text-gray-800 mb-2">
                                    Descrição da Agenda (Opcional):
                                </label>
                                <textarea id="descricao" name="descricao" rows="3" placeholder="Ex: Consultas online de 60 minutos via Google Meet. Aberto para novos clientes."
                                          class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-3 w-full text-lg"></textarea>
                                <p class="text-sm text-gray-500 mt-2">
                                    Adicione detalhes que ajudem seus clientes a entender sobre o que é esta agenda.
                                </p>
                            </div>

                            <div class="md:col-span-2"> {{-- Ocupa duas colunas para dar mais destaque --}}
                                <label for="limite_por_horario" class="block text-lg font-medium text-gray-800 mb-2">
                                    <span class="text-red-500">*</span> Quantas pessoas podem agendar no mesmo horário?
                                </label>
                                <input type="number" id="limite_por_horario" name="limite_por_horario" min="1" max="30" value="1"
                                       class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-3 w-full md:w-1/3 text-lg" required>
                                <p class="text-sm text-gray-500 mt-2">
                                    Se você atende apenas uma pessoa por vez, deixe "1". Se for um grupo ou evento, defina o número máximo de participantes (até 30).
                                </p>
                            </div>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-xl font-bold hover:bg-indigo-700 transition duration-300 ease-in-out shadow-md hover:shadow-lg flex items-center gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Criar Nova Agenda Agora
                        </button>
                    </form>
                </div>
            @else
                <div class="p-6 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 rounded-lg mb-8 shadow-md">
                    <p class="font-semibold text-lg flex items-center gap-2">
                        <svg class="h-6 w-6 text-yellow-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2A9 9 0 111 12a9 9 0 0118 0z" />
                        </svg>
                        Atenção: Você atingiu o limite de agendas.
                    </p>
                    <p class="mt-2 text-md">
                        Para criar mais agendas, entre em contato com o suporte ou gerencie suas agendas existentes.
                    </p>
                </div>
            @endif

            {{-- Lista de agendas existentes --}}
            <h2 class="font-bold text-3xl text-gray-900 leading-tight mb-8 mt-12 flex items-center gap-3">
                🗓️ Minhas Agendas Ativas
            </h2>
            <p class="text-md text-gray-700 mb-8">
                Aqui estão todas as agendas que você já criou. Clique em "Gerenciar Horários" para definir seus dias e horários de atendimento, ou edite as informações rápidas de cada uma.
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8"> {{-- Layout em grade para agendas --}}
                @forelse ($agendas as $agenda)
                    <div class="border border-gray-200 rounded-xl p-8 bg-white shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <div class="flex justify-between items-start mb-5">
                            <h3 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                                <svg class="h-7 w-7 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $agenda->titulo }}
                            </h3>
                            <a href="{{ route('agendas.gerenciar', $agenda) }}"
                               class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-sm font-semibold hover:bg-indigo-200 transition duration-200 flex items-center gap-2 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                                Gerenciar Horários
                            </a>
                        </div>

                        <p class="text-md text-gray-700 mb-6">{{ $agenda->descricao ?? 'Esta agenda ainda não tem uma descrição detalhada. Considere adicionar uma para facilitar a identificação.' }}</p>

                        {{-- Detalhes da agenda --}}
                        <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <p class="text-sm text-gray-600 mb-2"><span class="font-semibold">ID Único (Slug):</span> <code class="bg-gray-200 px-2 py-1 rounded text-xs">{{ $agenda->slug }}</code> (Usado para o link da sua agenda)</p>
                            <p class="text-sm text-gray-600"><span class="font-semibold">Limite por Horário:</span> {{ $agenda->limite_por_horario ?? 1 }} pessoa(s)</p>
                        </div>

                        {{-- Link público da agenda --}}
                        <div class="mt-4 mb-6 bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <p class="text-sm text-purple-800 font-semibold mb-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 015.656 5.656l-3.536 3.536a4 4 0 01-5.656-5.656m-4.95-4.95a4 4 0 015.657 0l3.535 3.536a4 4 0 01-5.657 5.657L6.34 16.83a4 4 0 010-5.657z" />
                                </svg>
                                Link Público da Agenda:
                            </p>
                            <div class="flex items-center gap-2 bg-white p-2 rounded-md border border-purple-100">
                                @php
                                    $publicUrl = route('agenda.publica', $agenda->slug);
                                @endphp
                                <input type="text" readonly value="{{ $publicUrl }}"
                                    class="flex-1 bg-transparent border-0 text-sm text-gray-700 focus:ring-0 cursor-text select-all">
                                <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $publicUrl }}'); this.innerText='Copiado!'; setTimeout(()=>this.innerText='Copiar',1500)"
                                        class="bg-purple-100 hover:bg-purple-200 text-purple-800 text-xs px-3 py-1 rounded-md font-semibold transition">
                                    Copiar
                                </button>
                                <a href="{{ $publicUrl }}" target="_blank"
                                class="bg-purple-600 hover:bg-purple-700 text-white text-xs px-3 py-1 rounded-md font-semibold transition">
                                Abrir
                                </a>
                            </div>
                        </div>


                        {{-- Form de edição rápida --}}
                        <details class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-100">
                            <summary class="cursor-pointer text-lg text-gray-800 font-semibold mb-3 hover:text-indigo-700 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Quer mudar algo? Edite rápido aqui:
                            </summary>
                            <form method="POST" action="{{ route('agendas.update', $agenda) }}" class="mt-4 space-y-4">
                                @csrf
                                @method('PUT')

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Novo Título:</label>
                                        <input type="text" name="titulo" value="{{ old('titulo', $agenda->titulo) }}" class="border-gray-300 rounded-md p-2 w-full text-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nova Descrição:</label>
                                        <input type="text" name="descricao" value="{{ old('descricao', $agenda->descricao) }}" class="border-gray-300 rounded-md p-2 w-full text-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Link da Agenda (Slug):</label>
                                        <input type="text" name="slug" value="{{ old('slug', $agenda->slug) }}" class="border-gray-300 rounded-md p-2 w-full text-md">
                                        <p class="text-xs text-gray-500 mt-1">É a parte final do link que seus clientes verão.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Limite de Pessoas por Horário:</label>
                                        <input type="number" name="limite_por_horario" min="1" max="30" value="{{ old('limite_por_horario', $agenda->limite_por_horario ?? 1) }}" class="border-gray-300 rounded-md p-2 w-full text-md">
                                        <p class="text-xs text-gray-500 mt-1">Máximo de 30 pessoas por horário.</p>
                                    </div>
                                </div>

                                <div class="text-right pt-4">
                                    <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-md font-semibold hover:bg-indigo-700 transition duration-200 shadow-sm">
                                        Salvar Edições
                                    </button>
                                </div>
                            </form>
                        </details>

                        {{-- Form de geração de horários --}}
                        <div x-data="agendaGenerator()" class="pt-6 border-t border-gray-100 mt-6">
                            <h4 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                                ➕ Crie Horários Disponíveis para Agendamento
                            </h4>
                            <p class="text-md text-gray-700 mb-6">
                                Use esta ferramenta para gerar seus horários de atendimento para um mês inteiro, de uma vez só!
                            </p>
                            <form method="POST" action="{{ route('agendas.gerarDisponibilidades', $agenda) }}">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Qual Mês?</label>
                                        <select name="mes" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-2.5 w-full">
                                            @foreach (range(1,12) as $m)
                                                <option value="{{ $m }}" @selected($m == now()->month)>
                                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Escolha o mês para gerar os horários.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Qual Ano?</label>
                                        <select name="ano" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-2.5 w-full">
                                            @foreach (range(now()->year, now()->year+2) as $y)
                                                <option value="{{ $y }}" @selected($y == now()->year)>{{ $y }}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Selecione o ano.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Horário de Início:</label>
                                        <input type="time" name="hora_inicio" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-2.5 w-full" value="09:00">
                                        <p class="text-xs text-gray-500 mt-1">A partir de que horas você estará disponível.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Horário de Fim:</label>
                                        <input type="time" name="hora_fim" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-2.5 w-full" value="17:00">
                                        <p class="text-xs text-gray-500 mt-1">Até que horas você estará disponível.</p>
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <p class="text-md font-medium text-gray-800 mb-3">Em quais dias da semana você atende?</p>
                                    <div class="flex flex-wrap gap-3">
                                        <template x-for="(day, key) in days" :key="key">
                                            <label class="flex items-center gap-2 cursor-pointer bg-gray-50 hover:bg-gray-100 p-3 rounded-xl border border-gray-200 transition duration-200"
                                                   :class="selectedDays.includes(day.value) ? 'bg-indigo-50 border-indigo-400 text-indigo-700 font-semibold shadow-sm' : ''">
                                                <input type="checkbox" :value="day.value" name="dias_semana[]" x-model="selectedDays" class="h-5 w-5 rounded text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                                <span x-text="day.label" class="text-md"></span>
                                            </label>
                                        </template>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-3">Selecione os dias da semana em que você deseja criar horários.</p>
                                </div>

                                <div class="mb-6">
                                    <label class="block text-md font-medium text-gray-800 mb-2">Duração de cada Atendimento (em minutos):</label>
                                    <input type="number" name="intervalo" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-lg p-3 w-full md:w-1/3 text-lg" placeholder="30" value="30" min="5" max="240">
                                    <p class="text-sm text-gray-500 mt-2">
                                        Por exemplo, se seus atendimentos duram 30 minutos, o sistema criará horários de 30 em 30 minutos.
                                    </p>
                                </div>

                                <button type="submit" class="bg-emerald-600 text-white px-8 py-3 rounded-xl text-xl font-bold hover:bg-emerald-700 transition duration-300 ease-in-out shadow-md hover:shadow-lg flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Gerar Meus Horários para o Mês!
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 bg-white border border-gray-200 rounded-xl shadow-lg text-center lg:col-span-2">
                        <p class="text-xl text-gray-700 font-medium mb-4">
                            Você ainda não criou nenhuma agenda.
                        </p>
                        <p class="text-md text-gray-600">
                            Use a seção "Crie uma Nova Agenda" acima para começar a configurar seus serviços e horários!
                        </p>
                        <div class="mt-6">
                            <svg class="mx-auto h-20 w-20 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        function agendaGenerator() {
            return {
                days: [
                    { label: 'Segunda-feira', value: 'monday' },
                    { label: 'Terça-feira', value: 'tuesday' },
                    { label: 'Quarta-feira', value: 'wednesday' },
                    { label: 'Quinta-feira', value: 'thursday' },
                    { label: 'Sexta-feira', value: 'friday' },
                    { label: 'Sábado', value: 'saturday' },
                    { label: 'Domingo', value: 'sunday' },
                ],
                selectedDays: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], // Dias de semana padrão selecionados
            }
        }
    </script>
</x-app-layout>