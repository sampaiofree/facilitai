@extends('layouts.agencia')

@section('content')
    @php
        $conexaoOptions = $conexoes->map(fn ($conexao) => [
            'id' => $conexao->id,
            'name' => $conexao->name,
            'cliente_id' => $conexao->cliente_id,
        ])->values();
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Webhooks</h2>
            <p class="text-sm text-slate-500">Crie links públicos para receber JSON e transformar payloads em leads, tags, campos e prompts.</p>
        </div>
        <button
            type="button"
            id="openWebhookLinkModal"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
            Criar link
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Nome</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Cliente</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Conexão</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Status</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">URL</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Última entrega</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($links as $link)
                    @php
                        $publicUrl = route('api.webhook-links.handle', ['token' => $link->token]);
                        $latestDelivery = $link->latestDelivery;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="font-medium text-slate-800">{{ $link->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">Criado em {{ optional($link->created_at)->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">{{ $link->cliente?->nome ?? '—' }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $link->conexao?->name ?? 'Sem conexão' }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $link->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                {{ $link->is_active ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <input
                                    type="text"
                                    readonly
                                    value="{{ $publicUrl }}"
                                    class="w-full min-w-[260px] rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
                                >
                                <button
                                    type="button"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    data-copy-link
                                    data-url="{{ $publicUrl }}"
                                >
                                    Copiar
                                </button>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-slate-600">
                            @if($latestDelivery)
                                <div class="font-medium text-slate-800">{{ ucfirst($latestDelivery->status) }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ optional($latestDelivery->processed_at ?? $latestDelivery->created_at)->format('d/m/Y H:i') }}</div>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <a
                                    href="{{ route('agencia.webhook-links.edit', $link) }}"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                >
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('agencia.webhook-links.status', $link) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $link->is_active ? 0 : 1 }}">
                                    <button
                                        type="submit"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                                    >
                                        {{ $link->is_active ? 'Desativar' : 'Ativar' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-500">
                            Nenhum webhook criado ainda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="webhookLinkModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Criar webhook</h3>
                <button type="button" data-close-webhook-link-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>

            <form method="POST" action="{{ route('agencia.webhook-links.store') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <label for="webhookClienteId" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</label>
                    <select id="webhookClienteId" name="cliente_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="webhookConexaoId" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conexão fixa (opcional)</label>
                    <select id="webhookConexaoId" name="conexao_id" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Sem conexão fixa</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-400">A conexão pode ficar vazia e ser definida depois na tela de edição.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" data-close-webhook-link-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Criar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('webhookLinkModal');
            const openButton = document.getElementById('openWebhookLinkModal');
            const closeButtons = modal.querySelectorAll('[data-close-webhook-link-modal]');
            const clienteSelect = document.getElementById('webhookClienteId');
            const conexaoSelect = document.getElementById('webhookConexaoId');
            const conexoes = @json($conexaoOptions);

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const refreshConexoes = () => {
                const clienteId = clienteSelect.value;
                const currentValue = conexaoSelect.value;
                const options = conexoes.filter((item) => String(item.cliente_id) === String(clienteId));

                conexaoSelect.innerHTML = '<option value="">Sem conexão fixa</option>';

                options.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name;
                    if (String(item.id) === String(currentValue)) {
                        option.selected = true;
                    }
                    conexaoSelect.appendChild(option);
                });
            };

            openButton.addEventListener('click', openModal);
            closeButtons.forEach((button) => button.addEventListener('click', closeModal));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            clienteSelect.addEventListener('change', refreshConexoes);
            refreshConexoes();

            document.querySelectorAll('[data-copy-link]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const url = button.dataset.url || '';
                    if (!url) {
                        return;
                    }

                    try {
                        await navigator.clipboard.writeText(url);
                        button.textContent = 'Copiado';
                        window.setTimeout(() => {
                            button.textContent = 'Copiar';
                        }, 1200);
                    } catch (error) {
                        window.prompt('Copie a URL manualmente:', url);
                    }
                });
            });
        })();
    </script>
@endsection
