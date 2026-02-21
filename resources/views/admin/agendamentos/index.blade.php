@extends('layouts.adm')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Agendamentos</h2>
            <p class="text-sm text-slate-500">Auditoria operacional de mensagens agendadas.</p>
        </div>
        <a
            href="{{ url(config('horizon.path', 'adm/horizon')) }}"
            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
        >
            Abrir Horizon
        </a>
    </div>

    <form method="GET" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-6">
        <div class="md:col-span-1">
            <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach(['pending', 'queued', 'sent', 'failed', 'canceled'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-1">
            <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Cliente</label>
            <select name="cliente_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected((int) ($filters['cliente_id'] ?? 0) === (int) $cliente->id)>
                        {{ $cliente->id }} - {{ $cliente->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-1">
            <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Assistente</label>
            <select name="assistant_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Todos</option>
                @foreach($assistants as $assistant)
                    <option value="{{ $assistant->id }}" @selected((int) ($filters['assistant_id'] ?? 0) === (int) $assistant->id)>
                        {{ $assistant->id }} - {{ $assistant->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-1">
            <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">De</label>
            <input type="date" name="date_start" value="{{ $filters['date_start'] ?? '' }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
        </div>

        <div class="md:col-span-1">
            <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Ate</label>
            <input type="date" name="date_end" value="{{ $filters['date_end'] ?? '' }}" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
        </div>

        <div class="md:col-span-1">
            <label class="block text-[11px] uppercase tracking-wide text-slate-400 mb-1">Busca</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nome ou telefone" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
        </div>

        <div class="md:col-span-6 flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Filtrar</button>
            <a href="{{ route('adm.agendamentos.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Limpar</a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Lead</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Assistente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Conexao</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Agendado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Tentativas</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Erro</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Criado em</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Acoes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($scheduledMessages as $scheduledMessage)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">{{ $scheduledMessage->id }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $scheduledMessage->clienteLead?->name ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $scheduledMessage->clienteLead?->phone ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $scheduledMessage->clienteLead?->cliente?->nome ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $scheduledMessage->assistant?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $scheduledMessage->conexao_id ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $scheduledMessage->status }}</td>
                        <td class="px-4 py-3">{{ $scheduledMessage->scheduled_for?->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $scheduledMessage->attempts }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-rose-700">{{ \Illuminate\Support\Str::limit($scheduledMessage->error_message, 80) }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $scheduledMessage->created_at?->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($scheduledMessage->status === 'pending')
                                    <form method="POST" action="{{ route('adm.agendamentos.cancel', $scheduledMessage) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-200">
                                            Cancelar
                                        </button>
                                    </form>
                                @endif

                                @if($scheduledMessage->status === 'failed')
                                    <form method="POST" action="{{ route('adm.agendamentos.retry', $scheduledMessage) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-md bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-200">
                                            Reprocessar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum agendamento encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $scheduledMessages->links('pagination::tailwind') }}
    </div>
@endsection

