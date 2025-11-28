<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-3">
            {{ __('Minhas Imagens') }}
        </h2>
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 14h.01M16 10h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-gray-700 text-sm leading-relaxed">
                                    🎥 <strong>Precisa de ajuda?</strong><br>
                                    Assista o vídeo e aprenda como instruir o assistente a enviar imagens, vídeos, áudios ou PDFs automaticamente.
                                </p>
                            </div>
                        </div>

                        <a href="https://youtu.be/yADDjjphelM" target="_blank"
                        class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M4 6h8m0 0v12m0-12L4 18" />
                            </svg>
                            Assistir tutorial
                        </a>
                    </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Seção de Upload -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Enviar Nova Imagem, Áudio, PDF ou Vídeo</h3>
                    

                    <form action="{{ route('images.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div>
                            <input type="file" name="image" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                            @error('image')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-xs text-gray-500">PNG, JPG ou MP4. Máximo de 10MB.</p>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm text-gray-600 mb-1" for="title">Titulo (opcional)</label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" maxlength="255" class="border rounded-lg px-3 py-2 text-sm w-full md:w-96" placeholder="Ex.: Apresentacao da campanha">
                            @error('title')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm text-gray-600 mb-1" for="description">Descricao (opcional)</label>
                            <textarea name="description" id="description" rows="3" maxlength="500" class="border rounded-lg px-3 py-2 text-sm w-full md:w-96" placeholder="Notas para lembrar o conteudo, ate 500 caracteres.">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm text-gray-600 mb-1" for="folder_id">Pasta (opcional)</label>
                            <select name="folder_id" id="folder_id" class="border rounded-lg px-3 py-2 text-sm w-full md:w-60">
                                <option value="">Sem pasta</option>
                                @foreach($folders as $folder)
                                    <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg">Enviar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Seção da Galeria -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg" role="alert">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg" role="alert">{{ session('error') }}</div>
                    @endif

                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-700 font-semibold">Você está em:</span>
                            <span class="text-sm text-gray-900">
                                @if($currentFolder)
                                    Pasta: {{ $currentFolder->name }} ({{ $images->total() }} arquivos)
                                @elseif($selectedFolderId === 'none')
                                    Sem pasta ({{ $images->total() }} arquivos)
                                @else
                                    Todas as pastas — arquivos sem pasta ({{ $images->total() }} arquivos)
                                @endif
                            </span>
                        </div>
                        @if($currentFolder || $selectedFolderId === 'none')
                            <a href="{{ route('images.index') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                Voltar
                            </a>
                        @else
                            <form method="GET" action="{{ route('images.index') }}" class="flex flex-wrap items-center gap-3">
                                <label for="filter-folder" class="text-sm text-gray-700">Filtrar por pasta:</label>
                                <select id="filter-folder" name="folder_id" class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                                    <option value="" {{ empty($selectedFolderId) ? 'selected' : '' }}>Todas</option>
                                    <option value="none" {{ $selectedFolderId === 'none' ? 'selected' : '' }}>Sem pasta</option>
                                    @foreach($folders as $folder)
                                        <option value="{{ $folder->id }}" {{ (string)$selectedFolderId === (string)$folder->id ? 'selected' : '' }}>
                                            {{ $folder->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </div>

                    @if($currentFolder)
                        <div class="mb-4 flex items-center gap-3 flex-wrap">
                            <form action="{{ route('folders.update', $currentFolder) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="name" value="{{ $currentFolder->name }}" class="border rounded-lg px-3 py-2 text-sm w-52" required>
                                <button type="submit" class="bg-blue-600 text-white text-sm font-semibold px-3 py-2 rounded-lg inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Renomear
                                </button>
                            </form>
                            <form action="{{ route('folders.destroy', $currentFolder) }}" method="POST" onsubmit="return confirm('Excluir pasta? Certifique-se de que ela esteja vazia.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-600 text-white text-sm font-semibold px-3 py-2 rounded-lg inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Excluir pasta
                                </button>
                            </form>
                        </div>
                    @endif

                    <form id="move-form" action="{{ route('images.move') }}" method="POST" class="mb-4">
                        @csrf
                        <div id="selection-bar" class="hidden bg-blue-50 border border-blue-100 text-blue-800 rounded-lg p-3 flex flex-wrap items-center gap-3">
                            <span class="text-sm font-semibold">Itens selecionados: <span id="selected-count">0</span></span>
                            <select name="folder_id" class="border rounded-lg px-3 py-2 text-sm">
                                <option value="">Mover para: Sem pasta</option>
                                @foreach($folders as $folder)
                                    <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                                @endforeach
                            </select>
                            <div id="move-form-images"></div>
                            <button type="submit" class="bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Mover selecionados</button>
                        </div>
                    </form>

                    @if($showFolders)
                        <div class="mb-6 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-3">
                                <h3 class="text-md font-semibold text-gray-800">Pastas</h3>
                                <form action="{{ route('folders.store') }}" method="POST" class="flex flex-wrap items-end gap-3">
                                    @csrf
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1" for="folder-name-inline">Nova pasta</label>
                                        <input id="folder-name-inline" type="text" name="name" required maxlength="255" class="border rounded-lg px-3 py-2 text-sm w-52" placeholder="Nome da pasta">
                                    </div>
                                    <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm">Criar</button>
                                </form>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse($folders as $folder)
                                    <a href="{{ route('images.index', ['folder_id' => $folder->id]) }}" class="border rounded-lg p-3 flex items-center gap-2 hover:border-blue-500 transition">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 7l2-3h5l2 3h10v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path></svg>
                                        <span class="font-semibold text-gray-800 truncate">{{ $folder->name }}</span>
                                    </a>
                                @empty
                                    <p class="text-sm text-gray-600">Nenhuma pasta criada ainda.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    @if(!$showFolders && !$currentFolder)
                        <p class="text-sm text-gray-700 mb-2 font-semibold">Arquivos sem pasta</p>
                    @endif


                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 w-10">
                                        <span class="sr-only">Selecionar</span>
                                    </th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Nome</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Tipo</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Pasta</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Tamanho</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Acoes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($images as $file)
                                    @php
                                        $ext = strtolower(pathinfo($file->url, PATHINFO_EXTENSION));
                                        $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']);
                                        $isAudio = in_array($ext, ['mp3']);
                                        $isPDF = in_array($ext, ['pdf']);
                                        $typeLabel = $isVideo ? 'Video' : ($isAudio ? 'Audio' : ($isPDF ? 'PDF' : 'Imagem'));
                                        $displayTitle = $file->title ?: $file->original_name;
                                        $hasDescription = !empty($file->description);
                                        $shortDescription = $hasDescription ? \Illuminate\Support\Str::limit($file->description, 80) : '-';
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 align-top">
                                            <input type="checkbox" class="image-checkbox h-4 w-4 mt-1" data-image-id="{{ $file->id }}">
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex flex-col gap-1">
                                                <span class="font-semibold text-gray-800 truncate max-w-xs" title="{{ $displayTitle }}">{{ $displayTitle }}</span>
                                                <p class="text-xs text-gray-600">
                                                    {{ $hasDescription ? $shortDescription : '-' }}
                                                </p>
                                                
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 align-top text-gray-700">
                                            {{ $typeLabel }}
                                        </td>
                                        <td class="px-3 py-2 align-top text-gray-700">
                                            {{ $file->folder->name ?? 'Sem pasta' }}
                                        </td>
                                        <td class="px-3 py-2 align-top text-gray-700">
                                            {{ $file->size }} KB
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" class="edit-image inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-md hover:bg-blue-200" data-update-url="{{ route('images.update', $file) }}" data-title="{{ e($file->title ?? '') }}" data-description="{{ e($file->description ?? '') }}" data-fallback-title="{{ e($file->original_name) }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-4-9l3 3m-4-3l-7 7v3h3l7-7"></path></svg>
                                                    Editar
                                                </button>
                                                <button type="button" class="copy-image-link inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-md hover:bg-gray-200" data-url="{{ $file->url }}" title="Copiar URL">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    Link
                                                </button>
                                                <form action="{{ route('images.destroy', $file) }}" method="POST" onsubmit="return confirm('Tem certeza?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Excluir" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-100 text-red-700 text-xs font-semibold rounded-md hover:bg-red-200">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                        Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">Nenhum arquivo enviado ainda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Links de Paginação --}}
                    <div class="mt-8">
                        {{ $images->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

<div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Editar midia</h3>
            <button type="button" id="edit-modal-close" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <form id="edit-form" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label for="edit-title" class="block text-sm text-gray-700 mb-1">Titulo</label>
                <input id="edit-title" type="text" name="title" maxlength="255" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Digite o titulo">
            </div>
            <div class="mb-4">
                <label for="edit-description" class="block text-sm text-gray-700 mb-1">Descricao</label>
                <textarea id="edit-description" name="description" rows="4" maxlength="500" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Digite a descricao"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="edit-cancel" class="px-4 py-2 text-sm font-semibold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = Array.from(document.querySelectorAll('.image-checkbox'));
        const selectionBar = document.getElementById('selection-bar');
        const selectedCount = document.getElementById('selected-count');
        const moveForm = document.getElementById('move-form');
        const imagesContainer = document.getElementById('move-form-images');

        const copyText = async (text) => {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        };

        const updateSelection = () => {
            const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.dataset.imageId);
            selectedCount.textContent = selected.length;
            selectionBar.classList.toggle('hidden', selected.length === 0);
        };

        checkboxes.forEach(cb => cb.addEventListener('change', updateSelection));

        moveForm.addEventListener('submit', (event) => {
            const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.dataset.imageId);
            if (selected.length === 0) {
                event.preventDefault();
                return;
            }
            imagesContainer.innerHTML = '';
            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'images[]';
                input.value = id;
                imagesContainer.appendChild(input);
            });
        });

        document.querySelectorAll('.copy-image-link').forEach(btn => {
            btn.addEventListener('click', async () => {
                const url = btn.dataset.url;
                try {
                    await copyText(url);
                    showAlert?.('URL copiada!', 'success');
                    btn.title = 'Copiado!';
                    btn.classList.add('bg-green-100');
                    setTimeout(() => {
                        btn.title = 'Copiar URL';
                        btn.classList.remove('bg-green-100');
                    }, 1500);
                } catch (e) {
                    showAlert?.('Nao foi possivel copiar o link.', 'error');
                }
            });
        });

        document.querySelectorAll('.view-description').forEach(btn => {
            btn.addEventListener('click', () => {
                const title = btn.dataset.title || 'Descricao';
                const description = btn.dataset.description || '';
                alert(`${title}\\n\\n${description}`);
            });
        });

        const editModal = document.getElementById('edit-modal');
        const editForm = document.getElementById('edit-form');
        const editTitle = document.getElementById('edit-title');
        const editDescription = document.getElementById('edit-description');
        const editClose = document.getElementById('edit-modal-close');
        const editCancel = document.getElementById('edit-cancel');

        const closeEditModal = () => {
            editModal.classList.add('hidden');
            editModal.classList.remove('flex');
        };

        document.querySelectorAll('.edit-image').forEach(btn => {
            btn.addEventListener('click', () => {
                editForm.action = btn.dataset.updateUrl;
                editTitle.value = btn.dataset.title || btn.dataset.fallbackTitle || '';
                editDescription.value = btn.dataset.description || '';
                editModal.classList.remove('hidden');
                editModal.classList.add('flex');
            });
        });

        [editClose, editCancel].forEach(el => {
            el?.addEventListener('click', closeEditModal);
        });

        editModal?.addEventListener('click', (event) => {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
    });
</script>
</x-app-layout>
