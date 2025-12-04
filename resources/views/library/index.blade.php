<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Minha Biblioteca
                </h2>
                <p class="text-sm text-gray-600">Armazene textos em Markdown e gere links públicos rápidos.</p>
            </div>
            <a href="{{ route('library.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Novo texto</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">

                    <form method="GET" action="{{ route('library.index') }}" class="flex flex-wrap items-center gap-3">
                        <input type="text" name="q" value="{{ $search }}" placeholder="Buscar por título ou conteúdo" class="border rounded-lg px-3 py-2 text-sm w-64">
                        <select name="sort" class="border rounded-lg px-3 py-2 text-sm">
                            <option value="created_at" {{ $sortField === 'created_at' ? 'selected' : '' }}>Data</option>
                            <option value="title" {{ $sortField === 'title' ? 'selected' : '' }}>Título</option>
                        </select>
                        <select name="direction" class="border rounded-lg px-3 py-2 text-sm">
                            <option value="desc" {{ $sortDirection === 'desc' ? 'selected' : '' }}>Desc</option>
                            <option value="asc" {{ $sortDirection === 'asc' ? 'selected' : '' }}>Asc</option>
                        </select>
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">Filtrar</button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 pr-4">Título</th>
                                    <th class="py-2 pr-4">Slug</th>
                                    <th class="py-2 pr-4">Criado em</th>
                                    <th class="py-2 pr-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                    <tr class="border-b">
                                        <td class="py-3 pr-4 font-semibold text-gray-800">{{ $entry->title }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $entry->slug }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="py-3 pr-4 text-right space-x-2">
                                            <button type="button"
                                                class="inline-flex items-center gap-1 text-xs text-blue-700 font-semibold open-entry-modal"
                                                data-entry-id="{{ $entry->id }}"
                                                data-title="{{ $entry->title }}"
                                                data-read-url="{{ route('library.public.show', $entry->slug) }}"
                                                data-edit-url="{{ route('library.public.edit', $entry->public_edit_token) }}"
                                                data-updated="{{ $entry->updated_at->format('d/m/Y H:i') }}"
                                                data-slug="{{ $entry->slug }}">
                                                Ver
                                            </button>
                                            <a href="{{ route('library.edit', $entry) }}" class="text-xs text-gray-700 font-semibold inline-flex items-center gap-1">Editar</a>
                                            <form action="{{ route('library.destroy', $entry) }}" method="POST" class="inline" onsubmit="return confirm('Excluir este texto?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-600 font-semibold inline-flex items-center gap-1">Excluir</button>
                                            </form>
                                            <textarea class="hidden entry-content" data-entry-id="{{ $entry->id }}">{{ $entry->content }}</textarea>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-gray-500">Nenhum texto encontrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $entries->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

<div id="entry-modal" class="fixed inset-0 z-40 hidden">
    <div class="absolute inset-0 bg-black/40" data-close-modal></div>
    <div class="relative z-50 flex items-start justify-center min-h-screen px-4 py-8 overflow-y-auto">
        <div class="bg-white w-full max-w-5xl rounded-xl shadow-2xl p-6 md:p-8 space-y-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="modal-title" class="text-xl font-semibold text-gray-900"></h3>
                    <p class="text-sm text-gray-500 mt-1" id="modal-updated"></p>
                    <p class="text-xs text-gray-400" id="modal-slug"></p>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700" data-close-modal aria-label="Fechar modal">✕</button>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="border rounded-lg p-3 bg-gray-50">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-gray-800">Link de leitura</span>
                        <button type="button" class="text-xs text-blue-700 font-semibold copy-link-btn" data-copy-target="read-link">Copiar</button>
                    </div>
                    <div id="modal-read-link" class="mt-2 text-xs text-gray-700 break-all"></div>
                </div>
                <div class="border rounded-lg p-3 bg-gray-50">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-gray-800">Link de edição</span>
                        <button type="button" class="text-xs text-blue-700 font-semibold copy-link-btn" data-copy-target="edit-link">Copiar</button>
                    </div>
                    <div id="modal-edit-link" class="mt-2 text-xs text-gray-700 break-all"></div>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-semibold text-gray-800">Conteúdo (Markdown)</label>
                    <button type="button" class="text-xs text-blue-700 font-semibold copy-link-btn" data-copy-target="content">Copiar conteúdo</button>
                </div>
                <textarea id="modal-content" readonly class="w-full border rounded-lg px-3 py-2 text-sm font-mono bg-gray-50 min-h-[240px] max-h-[50vh]"></textarea>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('entry-modal');
        const titleEl = document.getElementById('modal-title');
        const updatedEl = document.getElementById('modal-updated');
        const slugEl = document.getElementById('modal-slug');
        const readLinkEl = document.getElementById('modal-read-link');
        const editLinkEl = document.getElementById('modal-edit-link');
        const contentEl = document.getElementById('modal-content');

        const copyText = async (text) => {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            }
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        const openModal = (data) => {
            titleEl.textContent = data.title || '';
            updatedEl.textContent = data.updated ? `Atualizado em ${data.updated}` : '';
            slugEl.textContent = data.slug ? `Slug: ${data.slug}` : '';
            readLinkEl.textContent = data.readUrl || '';
            editLinkEl.textContent = data.editUrl || '';
            contentEl.value = data.content || '';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        document.querySelectorAll('.open-entry-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.entryId;
                const contentNode = document.querySelector(`.entry-content[data-entry-id="${id}"]`);
                const content = contentNode ? contentNode.value : '';
                openModal({
                    title: btn.dataset.title,
                    readUrl: btn.dataset.readUrl,
                    editUrl: btn.dataset.editUrl,
                    updated: btn.dataset.updated,
                    slug: btn.dataset.slug,
                    content,
                });
            });
        });

        modal.querySelectorAll('[data-close-modal]').forEach(el => {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        modal.querySelectorAll('.copy-link-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                let text = '';
                if (btn.dataset.copyTarget === 'read-link') {
                    text = readLinkEl.textContent;
                } else if (btn.dataset.copyTarget === 'edit-link') {
                    text = editLinkEl.textContent;
                } else if (btn.dataset.copyTarget === 'content') {
                    text = contentEl.value;
                }
                if (!text) return;
                const original = btn.textContent;
                try {
                    await copyText(text);
                    btn.textContent = 'Copiado!';
                    setTimeout(() => { btn.textContent = original; }, 1200);
                } catch (_) {
                    btn.textContent = 'Erro';
                    setTimeout(() => { btn.textContent = original; }, 1200);
                }
            });
        });
    });
</script>
</x-app-layout>
