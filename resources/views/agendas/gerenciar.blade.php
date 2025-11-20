<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <i class="fas fa-calendar-alt text-indigo-600"></i> Gerenciar Agendamentos — {{ $agenda->titulo }}
        </h2>
        <p class="text-sm text-gray-600">Aqui você organiza os horários da sua agenda. É fácil, basta seguir os passos!</p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- SEÇÃO 1: ESCOLHA O MÊS --}}
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-calendar-day text-indigo-500"></i> 1. Qual mês você quer organizar?
                </h3>

                @if($meses->isEmpty())
                    <div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i> Nenhuma data cadastrada ainda. Por favor, adicione horários primeiro.
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($meses as $m)
                            @php
                                $ym = $m->format('Y-m');
                                $ativo = ($mesSelecionado === $ym);
                            @endphp
                            <a href="{{ route('agendas.gerenciar', ['agenda' => $agenda->id, 'mes' => $ym]) }}"
                               class="block p-4 rounded-xl border-2 transition transform hover:scale-105
                                      {{ $ativo ? 'bg-indigo-600 border-indigo-700 text-white shadow-lg' : 'bg-white border-gray-200 text-gray-800 hover:bg-indigo-50 hover:text-white' }}">
                                <div class="text-base font-bold">{{ $m->translatedFormat('F') }}</div>
                                <div class="text-xs {{ $ativo ? 'opacity-90' : 'text-gray-500' }}">{{ $m->format('Y') }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- AVISO SE MÊS NÃO SELECIONADO --}}
            @unless($mesSelecionado)
                @if(!$meses->isEmpty())
                    <div class="p-4 bg-blue-100 border border-blue-300 text-blue-800 rounded-lg mb-8 flex items-center gap-2">
                        <i class="fas fa-hand-point-up text-blue-600 fa-lg"></i> Por favor, clique em um dos meses acima para continuar!
                    </div>
                @endif
            @endunless

            {{-- CONTEÚDO SÓ APARECE APÓS ESCOLHER O MÊS --}}
            @if($mesSelecionado)
                {{-- SEÇÃO 2: ENCONTRAR HORÁRIOS --}}
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-search text-indigo-500"></i> 2. Quer encontrar algo específico?
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">Use os filtros para achar os horários mais rápido.</p>

                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                        <input type="hidden" name="mes" value="{{ $mesSelecionado }}">

                        <div>
                            <label for="filtro_data" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="far fa-calendar-alt text-gray-500"></i> Data:
                            </label>
                            <input type="date" id="filtro_data" name="filtro_data"
                                   class="border-gray-300 rounded-md shadow-sm p-2 text-gray-700 w-full"
                                   value="{{ request('filtro_data') }}">
                        </div>

                        <div>
                            <label for="filtro_status" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-info-circle text-gray-500"></i> Situação:
                            </label>
                            <select id="filtro_status" name="filtro_status"
                                    class="border-gray-300 rounded-md shadow-sm p-2 text-gray-700 w-full">
                                <option value="">Todos</option>
                                <option value="livre" @selected(request('filtro_status') == 'livre')>Livre</option>
                                <option value="ocupado" @selected(request('filtro_status') == 'ocupado')>Ocupado</option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="filtro_busca" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-user-alt text-gray-500"></i> Buscar por Cliente:
                            </label>
                            <input type="text" id="filtro_busca" name="filtro_busca" placeholder="Nome ou Telefone do cliente"
                                   class="border-gray-300 rounded-md shadow-sm p-2 text-gray-700 w-full"
                                   value="{{ request('filtro_busca') }}">
                        </div>

                        <div class="flex flex-wrap gap-3 mt-2 sm:col-span-2 lg:col-span-4">
                            <button type="submit"
                                    class="bg-indigo-600 text-white px-5 py-2.5 rounded-md text-sm font-semibold hover:bg-indigo-700 transition flex items-center gap-1">
                                <i class="fas fa-filter"></i> Aplicar Filtros
                            </button>

                            @if(request()->has('filtro_data') || request()->has('filtro_status') || request()->has('filtro_busca'))
                                <a href="{{ route('agendas.gerenciar', ['agenda' => $agenda->id, 'mes' => $mesSelecionado]) }}"
                                   class="bg-gray-300 text-gray-800 px-5 py-2.5 rounded-md text-sm font-semibold hover:bg-gray-400 transition flex items-center gap-1">
                                    <i class="fas fa-times-circle"></i> Limpar Filtros
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- SEÇÃO 3: VER E ALTERAR HORÁRIOS --}}
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-list-ul text-indigo-500"></i> 3. Horários do mês de {{ \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)->translatedFormat('F \d\e Y') }}
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">Veja os agendamentos e faça as mudanças necessárias.</p>

                    {{-- FORM AÇÕES EM MASSA (fora da tabela, sem aninhamento) --}}
                    <form id="mass-action-form" method="POST" action="{{ route('disponibilidades.acoes-massa') }}" class="mb-6">
                        @csrf
                        <input type="hidden" name="agenda_id" value="{{ $agenda->id }}">

                        <div id="bulk-bar" class="hidden p-4 bg-blue-100 border border-blue-300 rounded-lg flex flex-wrap items-center gap-4 animate-fade-in">
                            <p class="text-sm text-blue-800 font-medium flex items-center gap-2">
                                <i class="fas fa-check-double text-blue-600"></i> <span id="bulk-count">0</span> horário(s) selecionado(s).
                            </p>
                            <select name="action" class="border-blue-300 rounded-md shadow-sm p-2 text-sm text-gray-700">
                                <option value="">Escolha o que fazer</option>
                                <option value="ocupar">Marcar como Ocupado <i class="fas fa-ban"></i></option>
                                <option value="desocupar">Marcar como Livre <i class="fas fa-check-circle"></i></option>
                                <option value="excluir">Excluir horários <i class="fas fa-trash-alt"></i></option>
                            </select>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-blue-700 transition flex items-center gap-1">
                                <i class="fas fa-play-circle"></i> Aplicar Ação
                            </button>
                        </div>
                    </form>

                    {{-- TABELA DE DISPONIBILIDADES (fora de qualquer form) --}}
                    <div class="overflow-x-auto bg-white border rounded-lg shadow-sm">
                        <table class="min-w-full text-sm text-left">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700 font-semibold">
                                    <th class="p-4 w-10">
                                        <input type="checkbox" id="check-all" class="rounded text-indigo-600 focus:ring-indigo-500">
                                    </th>
                                    <th class="p-4 w-1/3">Dia e Hora</th>
                                    <th class="p-4 w-1/4">Situação</th>
                                    <th class="p-4 w-1/3">Agendado Para</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($disponibilidades as $disp)
                                    <tr class="border-t hover:bg-gray-50 transition-colors {{ $disp->ocupado ? 'bg-emerald-50' : '' }}">
                                        <td class="p-4">
                                            <input type="checkbox"
                                                   name="disponibilidade_ids[]"
                                                   value="{{ $disp->id }}"
                                                   class="row-check rounded text-indigo-600 focus:ring-indigo-500"
                                                   form="mass-action-form">
                                        </td>
                                        <td class="p-4">
                                            <span class="font-medium">
                                                <i class="far fa-calendar-alt text-gray-500 mr-1"></i> {{ \Carbon\Carbon::parse($disp->data)->translatedFormat('D, d/m') }}
                                            </span>
                                            <span class="text-gray-600 ml-2">
                                                <i class="far fa-clock text-gray-500 mr-1"></i> {{ $disp->inicio }} - {{ $disp->fim }}
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            @if($disp->ocupado)
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                    <i class="fas fa-check-circle"></i> Ocupado
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <i class="fas fa-lock-open"></i> Livre
                                                </span>
                                            @endif
                                        </td>
                                        <td class="p-4">
                                            @if($disp->ocupado)
                                                <div>
                                                    <p class="font-medium text-gray-800 flex items-center gap-1">
                                                        <i class="fas fa-user"></i> {{ $disp->nome ?? 'N/A' }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                                        <i class="fas fa-phone-alt"></i> {{ $disp->telefone ?? 'N/A' }}
                                                    </p>
                                                </div>
                                            @else
                                                <span class="text-gray-400 italic flex items-center gap-1">
                                                    <i class="fas fa-tag"></i> Disponível para agendamento
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-gray-500 p-6 bg-gray-50">
                                            <i class="fas fa-box-open fa-2x mb-3 text-gray-400"></i>
                                            <p>Ops! Não encontramos nenhum horário para
                                                <span class="font-semibold">{{ \Carbon\Carbon::createFromFormat('Y-m', $mesSelecionado)->translatedFormat('F \d\e Y') }}</span>
                                                com os filtros aplicados.
                                            </p>
                                            <p class="mt-2 text-sm">Tente limpar os filtros ou escolha outro mês.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginação --}}
                    @if ($disponibilidades instanceof \Illuminate\Pagination\LengthAwarePaginator && $disponibilidades->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $disponibilidades->links() }}
                        </div>
                    @endif
                </div>

                {{-- JS vanilla para selecionar todos e mostrar a barra de ações --}}
                <script>
                    (function () {
                        const checkAll = document.getElementById('check-all');
                        const rowChecks = document.querySelectorAll('.row-check');
                        const bulkBar   = document.getElementById('bulk-bar');
                        const bulkCount = document.getElementById('bulk-count');

                        function updateBulk() {
                            const selected = Array.from(rowChecks).filter(c => c.checked).length;
                            bulkCount.textContent = selected;
                            if (selected > 0) {
                                bulkBar.classList.remove('hidden');
                            } else {
                                bulkBar.classList.add('hidden');
                            }
                        }

                        if (checkAll) {
                            checkAll.addEventListener('change', function () {
                                rowChecks.forEach(c => c.checked = checkAll.checked);
                                updateBulk();
                            });
                        }
                        rowChecks.forEach(c => c.addEventListener('change', function () {
                            if (!this.checked && checkAll) checkAll.checked = false;
                            updateBulk();
                        }));
                        updateBulk(); // Executar ao carregar para verificar checkboxes pre-selecionados
                    })();
                </script>
            @endif
        </div>
    </div>
</x-app-layout>