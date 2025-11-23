<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gerenciamento de Conversas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    @if (session('success'))
                        <div class="px-4 py-3 bg-green-100 text-green-800 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('warning'))
                        <div class="px-4 py-3 bg-yellow-100 text-yellow-800 rounded-md">
                            {{ session('warning') }}
                        </div>
                    @endif

                    <div class="rounded-lg border border-gray-100 bg-gray-50/60 p-4">
                        <form action="{{ route('chats.index') }}" method="GET" class="grid gap-4 md:grid-cols-4">
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Busca</label>
                                <div class="relative mt-1">
                                    <input type="text" name="search" placeholder="Conv ID, contato ou nome"
                                        value="{{ $filters['search'] ?? '' }}"
                                        class="w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-md px-4 py-2 pl-10">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-sm">🔍</span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Instância</label>
                                <select name="instance_id" class="mt-1 w-full border-gray-200 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Todas</option>
                                    @foreach ($instances as $instance)
                                        <option value="{{ $instance->id }}" {{ $filters['instance_id'] == $instance->id ? 'selected' : '' }}>
                                            {{ $instance->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Assistente</label>
                                <select name="assistant_id" class="mt-1 w-full border-gray-200 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Todos</option>
                                    @foreach ($assistants as $assistant)
                                        <option value="{{ $assistant->id }}" {{ $filters['assistant_id'] == $assistant->id ? 'selected' : '' }}>
                                            {{ $assistant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</label>
                                <select name="aguardando_atendimento" class="mt-1 w-full border-gray-200 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Todos</option>
                                    <option value="1" {{ $filters['aguardando_atendimento'] === '1' ? 'selected' : '' }}>Aguardando</option>
                                    <option value="0" {{ $filters['aguardando_atendimento'] === '0' ? 'selected' : '' }}>Atendidos</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Ordenar</label>
                                <select name="order" class="mt-1 w-full border-gray-200 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="updated_at_desc" {{ $filters['order'] === 'updated_at_desc' ? 'selected' : '' }}>Atualização ↓</option>
                                    <option value="updated_at_asc" {{ $filters['order'] === 'updated_at_asc' ? 'selected' : '' }}>Atualização ↑</option>
                                    <option value="created_at_desc" {{ $filters['order'] === 'created_at_desc' ? 'selected' : '' }}>Criação ↓</option>
                                    <option value="created_at_asc" {{ $filters['order'] === 'created_at_asc' ? 'selected' : '' }}>Criação ↑</option>
                                </select>
                            </div>

                            <div class="md:col-span-4 flex flex-wrap gap-3 justify-end">
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-white font-semibold shadow hover:bg-blue-500">
                                    ⚙️ Aplicar filtros
                                </button>
                                <a href="{{ route('chats.index') }}"
                                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-white">
                                    Limpar
                                </a>
                            </div>
                        </form>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <a href="{{ route('chats.export', request()->query()) }}"
                            class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow hover:bg-gray-50">
                            ⬇️ Exportar CSV
                        </a>
        <form id="bulk-form" method="POST" action="{{ route('chats.bulk_attended') }}" class="inline-flex">
            @csrf
            <button type="button" id="bulk-mark-attended"
                class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-500">
                ✅ Marcar selecionados
            </button>
        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">
                                        <input type="checkbox" id="select-all-chats" class="h-4 w-4 text-blue-600">
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contato
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aguardando
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Bot
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($chats as $chat)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <input type="checkbox" class="bulk-checkbox h-4 w-4 text-blue-600"
                                                value="{{ $chat->id }}">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-900">{{ $chat->nome ?? 'Sem nome' }}</div>
                                            <p class="text-xs text-gray-500">Atualizado {{ $chat->updated_at?->diffForHumans() }}</p>
                                        </td>
                                        <td class="px-4 py-3 font-mono text-sm text-gray-800">
                                            {{ $chat->contact }}
                                            <div class="text-xs text-gray-500">{{ $chat->conv_id }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="px-2 inline-flex text-xs font-semibold rounded-full {{ $chat->aguardando_atendimento ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $chat->aguardando_atendimento ? 'Sim' : 'Não' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <form action="{{ route('chats.toggle_bot', $chat) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center rounded-full px-4 py-1 text-xs font-semibold shadow {{ $chat->bot_enabled ? 'bg-emerald-500 text-white hover:bg-emerald-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                                    {{ $chat->bot_enabled ? 'Ativo' : 'Pausado' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-col gap-2 text-xs font-semibold">
                                                <button type="button"
                                                    class="w-full rounded-md border border-gray-200 px-3 py-2 text-gray-700 hover:bg-gray-50 view-chat-btn"
                                                    data-chat-nome="{{ $chat->nome ?? '—' }}"
                                                    data-chat-informacoes="{{ $chat->informacoes ?? 'Sem informações adicionais.' }}"
                                                    data-chat-conv="{{ $chat->conv_id }}"
                                                    data-chat-assistente="{{ $chat->assistant?->name ?? $chat->assistente?->name ?? '—' }}"
                                                    data-chat-instancia="{{ $chat->instance?->name ?? '—' }}">
                                                    Ver
                                                </button>

                                                <button type="button"
                                                    class="w-full rounded-md border border-blue-200 px-3 py-2 text-blue-700 hover:bg-blue-50 edit-chat-btn"
                                                    data-chat-id="{{ $chat->id }}"
                                                    data-chat-action="{{ route('chats.update', $chat) }}"
                                                    data-chat-nome="{{ $chat->nome }}"
                                                    data-chat-informacoes="{{ $chat->informacoes }}"
                                                    data-chat-aguardando="{{ $chat->aguardando_atendimento ? '1' : '0' }}">
                                                    Editar
                                                </button>

                                                <form action="{{ route('chats.destroy', $chat) }}" method="POST"
                                                    class="delete-chat-form w-full">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-full rounded-md bg-red-50 px-3 py-2 text-red-600 hover:bg-red-100">
                                                        Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
                                            Nenhuma conversa encontrada com os filtros atuais.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $chats->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="viewModal" class="fixed inset-0 hidden z-40 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Detalhes do chat</h3>
                <button type="button" id="closeViewModal" class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
            <dl class="mt-4 space-y-3 text-sm text-gray-700">
                <div>
                    <dt class="font-semibold text-gray-600 uppercase text-xs">Nome</dt>
                    <dd id="viewNome" class="mt-1 text-gray-900"></dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-600 uppercase text-xs">Informações</dt>
                    <dd id="viewInformacoes" class="mt-1 text-gray-900 whitespace-pre-wrap"></dd>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="font-semibold text-gray-600 uppercase text-xs">Conv ID</dt>
                        <dd id="viewConv" class="mt-1 font-mono text-gray-900 text-xs"></dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-gray-600 uppercase text-xs">Assistente</dt>
                        <dd id="viewAssistente" class="mt-1 text-gray-900"></dd>
                    </div>
                </div>
                <div>
                    <dt class="font-semibold text-gray-600 uppercase text-xs">Instância</dt>
                    <dd id="viewInstancia" class="mt-1 text-gray-900"></dd>
                </div>
            </dl>
            <div class="mt-6 flex justify-end">
                <button type="button" id="closeViewModalFooter"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Editar chat</h3>
                <button type="button" id="closeEditModal" class="text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
            <form id="editChatForm" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Nome</label>
                    <input type="text" name="nome" id="modalNome"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Informações</label>
                    <textarea name="informacoes" id="modalInformacoes" rows="3"
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <input type="checkbox" name="aguardando_atendimento" id="modalAguardando" value="1"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Aguardando atendimento
                </label>
                <div class="flex items-center justify-end gap-3">
                    <button type="button" id="cancelEdit"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-500">
                        Salvar alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('form.delete-chat-form');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Tem certeza?',
                        text: `Isso excluirá o histórico de conversa com este contato.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sim, excluir!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            this.submit();
                        }
                    });
                });
            });

            const selectAll = document.getElementById('select-all-chats');
            const checkboxes = document.querySelectorAll('input.bulk-checkbox');
            selectAll?.addEventListener('change', function() {
                checkboxes.forEach(box => {
                    box.checked = this.checked;
                });
            });

            const bulkForm = document.getElementById('bulk-form');
            const bulkButton = document.getElementById('bulk-mark-attended');

            bulkButton?.addEventListener('click', function() {
                const selected = document.querySelectorAll('input.bulk-checkbox:checked');
                if (!selected.length) {
                    Swal.fire('Selecione ao menos um chat.');
                    return;
                }

                bulkForm.querySelectorAll('input[name="selected[]"]').forEach(node => node.remove());
                selected.forEach(box => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected[]';
                    input.value = box.value;
                    bulkForm.appendChild(input);
                });

                bulkForm.submit();
            });

            const modal = document.getElementById('editModal');
            const form = document.getElementById('editChatForm');
            const nomeField = document.getElementById('modalNome');
            const infoField = document.getElementById('modalInformacoes');
            const aguardandoField = document.getElementById('modalAguardando');
            const closeButtons = [document.getElementById('closeEditModal'), document.getElementById('cancelEdit')];

            document.querySelectorAll('.edit-chat-btn').forEach(button => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.chatAction;
                    nomeField.value = button.dataset.chatNome ?? '';
                    infoField.value = button.dataset.chatInformacoes ?? '';
                    aguardandoField.checked = button.dataset.chatAguardando === '1';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            closeButtons.forEach(btn => btn?.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }));

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });

            const viewModal = document.getElementById('viewModal');
            const viewFields = {
                nome: document.getElementById('viewNome'),
                info: document.getElementById('viewInformacoes'),
                conv: document.getElementById('viewConv'),
                assistente: document.getElementById('viewAssistente'),
                instancia: document.getElementById('viewInstancia'),
            };
            const closeViewButtons = [
                document.getElementById('closeViewModal'),
                document.getElementById('closeViewModalFooter'),
            ];

            document.querySelectorAll('.view-chat-btn').forEach(button => {
                button.addEventListener('click', () => {
                    viewFields.nome.textContent = button.dataset.chatNome ?? '—';
                    viewFields.info.textContent = button.dataset.chatInformacoes ?? '—';
                    viewFields.conv.textContent = button.dataset.chatConv ?? '—';
                    viewFields.assistente.textContent = button.dataset.chatAssistente ?? '—';
                    viewFields.instancia.textContent = button.dataset.chatInstancia ?? '—';
                    viewModal.classList.remove('hidden');
                    viewModal.classList.add('flex');
                });
            });

            closeViewButtons.forEach(btn => btn?.addEventListener('click', () => {
                viewModal.classList.add('hidden');
                viewModal.classList.remove('flex');
            }));

            viewModal.addEventListener('click', (event) => {
                if (event.target === viewModal) {
                    viewModal.classList.add('hidden');
                    viewModal.classList.remove('flex');
                }
            });
        });
    </script>
@endpush
</x-app-layout>
