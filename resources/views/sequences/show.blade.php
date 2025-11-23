<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500 uppercase font-semibold">Sequência</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $sequence->name }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('sequences.index') }}"
                   class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-white">
                    Voltar
                </a>
                <a href="{{ route('sequences.edit', $sequence) }}"
                   class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-500">
                    Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center gap-4">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $sequence->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                        {{ $sequence->active ? 'Ativa' : 'Inativa' }}
                    </span>
                    <span class="text-sm text-gray-600">Passos: <strong>{{ $sequence->steps->count() }}</strong></span>
                    <span class="text-sm text-gray-600">Criada {{ $sequence->created_at?->diffForHumans() }}</span>
                </div>
                @if($sequence->description)
                    <p class="text-sm text-gray-700">{{ $sequence->description }}</p>
                @endif

                <div class="grid md:grid-cols-4 gap-4">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Inscritos (todos)</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $totalChats }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Em andamento</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-700">{{ $resumoStatus['em_andamento'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Concluídos</p>
                        <p class="mt-1 text-2xl font-bold text-blue-700">{{ $resumoStatus['concluida'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Cancelados/Pausados</p>
                        <p class="mt-1 text-2xl font-bold text-red-700">
                            {{ ($resumoStatus['cancelada'] ?? 0) + ($resumoStatus['pausada'] ?? 0) }}
                        </p>
                    </div>
                </div>

                @if($sequence->steps->count())
                    <div class="rounded-lg border border-gray-100 p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Chats por passo (em andamento)</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($sequence->steps as $step)
                                <span class="px-3 py-2 rounded-md border border-gray-200 text-sm text-gray-800 bg-white">
                                    Passo {{ $step->ordem }}@if($step->title) — {{ $step->title }}@endif:
                                    <strong>{{ $porPasso[$step->ordem] ?? 0 }}</strong>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <form method="GET" action="{{ route('sequences.show', $sequence) }}" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="search" value="{{ request('search') }}">
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</label>
                            <select name="status" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                @foreach (['em_andamento' => 'Em andamento', 'pausada' => 'Pausada', 'concluida' => 'Concluída', 'cancelada' => 'Cancelada'] as $value => $label)
                                    <option value="{{ $value }}" {{ $statusFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Passo</label>
                            <select name="passo" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                @foreach ($sequence->steps as $step)
                                    <option value="{{ $step->ordem }}" {{ (string)$passoFilter === (string)$step->ordem ? 'selected' : '' }}>
                                        Passo {{ $step->ordem }}@if($step->title) - {{ $step->title }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow hover:bg-blue-500">
                                Aplicar
                            </button>
                            <a href="{{ route('sequences.show', $sequence) }}" class="text-sm text-gray-600 hover:underline">Limpar</a>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Chat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contato</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Passo</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Próximo envio</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Iniciado</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($sequenceChats as $seqChat)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-900">{{ $seqChat->chat?->nome ?? 'Sem nome' }}</div>
                                        <div class="text-xs text-gray-500">{{ $seqChat->chat?->conv_id ?? '—' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm text-gray-800">
                                        {{ $seqChat->chat?->contact ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        @php $totalPassos = $sequence->steps->count(); @endphp
                                        @if($seqChat->step)
                                            Passo {{ $seqChat->step->ordem }}@if($seqChat->step->title) — {{ $seqChat->step->title }}@endif
                                            / {{ $totalPassos }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            @class([
                                                'bg-emerald-100 text-emerald-800' => $seqChat->status === 'em_andamento',
                                                'bg-yellow-100 text-yellow-800' => $seqChat->status === 'pausada',
                                                'bg-blue-100 text-blue-800' => $seqChat->status === 'concluida',
                                                'bg-red-100 text-red-800' => $seqChat->status === 'cancelada',
                                            ])">
                                            {{ ucfirst(str_replace('_', ' ', $seqChat->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        {{ $seqChat->proximo_envio_em ? $seqChat->proximo_envio_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        {{ $seqChat->iniciado_em ? $seqChat->iniciado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <form action="{{ route('sequences.chats.destroy', [$sequence, $seqChat]) }}" method="POST" class="inline-block"
                                              onsubmit="return confirm('Remover este chat da sequência? Os logs desta inscrição serão apagados.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 text-sm font-semibold hover:underline">Excluir</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                        Nenhum chat encontrado para os filtros selecionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $sequenceChats->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
