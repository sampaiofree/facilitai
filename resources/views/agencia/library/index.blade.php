@extends('layouts.agencia')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Biblioteca por cliente</h2>
            <p class="text-sm text-slate-500">Cadastre templates de conteúdo que podem ser reutilizados nas campanhas.</p>
        </div>
        <button
            type="button"
            id="open-library-modal"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
        >Novo registro</button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-slate-600">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Cliente</th>
                        <th class="px-3 py-2 text-left font-semibold">Título</th>
                        <th class="px-3 py-2 text-left font-semibold">Publicado em</th>
                        <th class="px-3 py-2 text-left font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="px-3 py-3 font-medium text-slate-900">{{ $entry->cliente->nome ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $entry->title }}</td>
                            <td class="px-3 py-3">{{ $entry->created_at?->format('d/m/Y') }}</td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="entry-edit inline-flex items-center gap-1 rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:border-slate-900 hover:text-slate-900"
                                        data-entry="{{ json_encode([
                                            'id' => $entry->id,
                                            'title' => $entry->title,
                                            'content' => $entry->content,
                                            'cliente_id' => $entry->cliente_id,
                                        ], JSON_UNESCAPED_UNICODE) }}"
                                    >Editar</button>
                                    <button
                                        type="button"
                                        class="copy-library-link inline-flex items-center gap-1 rounded-full border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600 hover:border-slate-900 hover:text-slate-900"
                                        data-url="{{ url('/biblioteca/' . $entry->slug) }}"
                                    >Copiar link</button>
                                    <form action="{{ route('agencia.library.destroy', $entry) }}" method="POST" onsubmit="return confirm('Excluir este registro?');">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-[11px] font-semibold text-rose-600 hover:border-rose-300"
                                        >Excluir</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-slate-400">Nenhum registro criado ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $entries->links() }}
        </div>
    </div>

    <div id="library-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 px-4 py-6">
        <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 id="library-modal-title" class="text-lg font-semibold text-slate-900">Novo registro</h3>
                <button type="button" data-library-close class="text-slate-500 hover:text-slate-700">×</button>
            </div>
            <form
                id="library-form"
                method="POST"
                action="{{ route('agencia.library.store') }}"
                data-create-route="{{ route('agencia.library.store') }}"
                data-update-route-template="{{ route('agencia.library.update', ['libraryEntry' => '__ID__']) }}"
                class="space-y-4"
            >
                @csrf
                <input type="hidden" name="_method" value="POST" id="library-form-method">

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="libraryTitle">Título</label>
                    <input id="libraryTitle" name="title" type="text" required maxlength="255" class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none">
                </div>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="libraryCliente">Cliente</label>
                    <select
                        id="libraryCliente"
                        name="cliente_id"
                        required
                        class="mt-1 w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"
                    >
                        <option value="">Selecione um cliente</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-400" for="libraryContent">Conteúdo</label>
                    <textarea id="libraryContent" name="content" rows="5" maxlength="1500" class="mt-1 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-slate-400 focus:outline-none"></textarea>
                    <p class="mt-1 text-[11px] text-slate-400">Conteúdo até 1500 caracteres. O slug é gerado automaticamente.</p>
                </div>

                <div class="flex justify-end gap-3 pt-3">
                    <button type="button" data-library-close class="rounded-2xl border border-slate-200 px-4 py-1 text-[12px] font-semibold text-slate-600 hover:border-slate-400">Cancelar</button>
                    <button type="submit" id="library-form-submit" class="rounded-2xl bg-blue-600 px-4 py-1 text-[12px] font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('library-modal');
            const form = document.getElementById('library-form');
            const openButton = document.getElementById('open-library-modal');
            const closeButtons = document.querySelectorAll('[data-library-close]');
            const titleInput = document.getElementById('libraryTitle');
            const contentInput = document.getElementById('libraryContent');
            const clienteSelect = document.getElementById('libraryCliente');
            const methodInput = document.getElementById('library-form-method');
            const modalTitle = document.getElementById('library-modal-title');
            const submitButton = document.getElementById('library-form-submit');
            const updateRouteTemplate = form.dataset.updateRouteTemplate;
            const createRoute = form.dataset.createRoute;

            const openModal = () => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            const resetForm = () => {
                form.reset();
                methodInput.value = 'POST';
                form.action = createRoute;
                modalTitle.textContent = 'Novo registro';
                submitButton.textContent = 'Salvar';
            };

            openButton?.addEventListener('click', () => {
                resetForm();
                openModal();
            });

            closeButtons.forEach(btn => btn.addEventListener('click', closeModal));

        document.querySelectorAll('.entry-edit').forEach(button => {
            button.addEventListener('click', () => {
                const entry = JSON.parse(button.dataset.entry || '{}');
                    if (!entry.id) return;

                    resetForm();
                    methodInput.value = 'PUT';
                    form.action = updateRouteTemplate.replace('__ID__', entry.id);
                    modalTitle.textContent = 'Editar registro';
                    submitButton.textContent = 'Atualizar';

                    titleInput.value = entry.title || '';
                    contentInput.value = entry.content || '';
                    clienteSelect.value = entry.cliente_id || '';

                    openModal();
                });
            });

            modal?.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.copy-library-link').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const url = btn.dataset.url;
                    if (!url) return;
                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(url);
                        } else {
                            const textarea = document.createElement('textarea');
                            textarea.value = url;
                            textarea.setAttribute('readonly', '');
                            textarea.style.position = 'absolute';
                            textarea.style.left = '-9999px';
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textarea);
                        }
                        if (typeof showAlert === 'function') {
                            showAlert('Link copiado!', 'success');
                        }
                    } catch (e) {
                        if (typeof showAlert === 'function') {
                            showAlert('Não foi possível copiar o link.', 'error');
                        } else {
                            alert('Não foi possível copiar o link.');
                        }
                    }
                });
            });
        });
    </script>
@endpush
