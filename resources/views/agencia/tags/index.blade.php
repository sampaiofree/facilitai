@extends('layouts.agencia')

@section('content')
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ session('warning') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Tags</h2>
            <p class="text-sm text-slate-500">Gerencie as tags vinculadas aos seus clientes. Tags globais legadas ficam apenas na tela de diagnóstico.</p>
        </div>
        <button
            id="openTagModal"
            type="button"
            @disabled($clientes->isEmpty())
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-blue-600"
        >
            Nova tag
        </button>
    </div>

    @if($clientes->isEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Cadastre ao menos um cliente antes de criar tags.
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Cliente</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Descrição</th>
                    <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tags as $tag)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $tag->name }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $tag->cliente?->nome ?? ('Cliente #' . $tag->cliente_id) }}</td>
                        <td class="px-5 py-4 text-slate-600">{{ $tag->description ?? '—' }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                    data-action="edit-tag"
                                    data-payload="{{ json_encode([
                                        'id' => $tag->id,
                                        'name' => $tag->name,
                                        'color' => $tag->color,
                                        'description' => $tag->description,
                                        'cliente_id' => $tag->cliente_id,
                                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                >Editar</button>
                                <form method="POST" action="{{ route('agencia.tags.destroy', $tag) }}" onsubmit="return confirm('Deseja excluir esta tag?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-6 text-center text-slate-500">Nenhuma tag cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="tagModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="tagModalTitle" class="text-lg font-semibold text-slate-900">Nova tag</h3>
                <button type="button" data-close-modal class="text-slate-500 hover:text-slate-700">x</button>
            </div>
            <form id="tagForm" method="POST" action="{{ route('agencia.tags.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="tag_id" id="tagId" value="">

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="tagName">Nome</label>
                    <input id="tagName" name="name" type="text" maxlength="50" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="tagCliente">Cliente</label>
                    <select id="tagCliente" name="cliente_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="" disabled selected>Selecione um cliente</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="tagDescription">Descrição</label>
                    <textarea id="tagDescription" name="description" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" data-close-modal class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('tagModal');
            const openBtn = document.getElementById('openTagModal');
            const closeBtns = modal.querySelectorAll('[data-close-modal]');
            const form = document.getElementById('tagForm');
            const title = document.getElementById('tagModalTitle');
            const tagIdInput = document.getElementById('tagId');
            const nameInput = document.getElementById('tagName');
            const descriptionInput = document.getElementById('tagDescription');
            const clienteSelect = document.getElementById('tagCliente');

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                tagIdInput.value = '';
                form.reset();
                title.textContent = 'Nova tag';
                if (clienteSelect) {
                    clienteSelect.value = '';
                }
            };

            openBtn.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeBtns.forEach(button => button.addEventListener('click', closeModal));
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.querySelectorAll('[data-action="edit-tag"]').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.dataset.payload);
                    tagIdInput.value = data.id;
                    nameInput.value = data.name || '';
                    descriptionInput.value = data.description || '';
                    if (clienteSelect) {
                        clienteSelect.value = data.cliente_id ?? '';
                    }
                    title.textContent = 'Editar tag';
                    openModal();
                });
            });
        })();
    </script>
@endsection
