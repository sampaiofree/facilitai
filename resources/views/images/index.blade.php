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
                            <p class="mt-2 text-xs text-gray-500">PNG, JPG ou MP4. Máximo de 2MB.</p>
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

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                       @forelse ($images as $file)
    @php
        // Pega a extensão do arquivo
        $ext = strtolower(pathinfo($file->url, PATHINFO_EXTENSION));
        $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']);
        $isAudio = in_array($ext, ['mp3']);
        $isPDF = in_array($ext, ['pdf']);
    @endphp

    <div x-data="{ url: '{{ $file->url }}' }" class="relative group border rounded-lg overflow-hidden">
        <label class="absolute top-2 left-2 z-20 bg-white/80 rounded-md p-1 shadow">
            <input type="checkbox" class="image-checkbox h-4 w-4" data-image-id="{{ $file->id }}">
        </label>
        @if($isVideo)
            {{-- Imagem padrão para vídeos --}}
            <div class="h-40 w-full bg-gray-800 flex items-center justify-center relative">
                <img src="/storage/user_images/1/videoFundo.jpg" class="absolute inset-0 w-full h-full object-cover opacity-50">
                <span class="text-white font-semibold text-sm text-center px-2 break-words z-10">
                    Vídeo: {{ $file->original_name }}
                </span>
            </div>
        @elseif($isAudio)
            <div class="h-40 w-full bg-gray-800 flex items-center justify-center relative">
                <img src="/storage/user_images/1/fundomp3.jpg" class="absolute inset-0 w-full h-full object-cover opacity-50">
                <span class="text-white font-semibold text-sm text-center px-2 break-words z-10">
                    Audio: {{ $file->original_name }}
                </span>
            </div>
        @elseif($isPDF)
            {{-- Imagem padrão para PDFs --}}
            <div class="h-40 w-full bg-gray-800 flex items-center justify-center relative">
                <img src="/storage/user_images/1/fundopdf.jpg" class="absolute inset-0 w-full h-full object-cover opacity-50">
                <span class="text-white font-semibold text-sm text-center px-2 break-words z-10">
                    PDF: {{ $file->original_name }}
                </span>
            </div>
        @else
            {{-- Se for imagem, exibe a própria imagem --}}
            <img src="{{ $file->url }}" alt="{{ $file->original_name }}" class="h-40 w-full object-cover">
        @endif

        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-60 transition-all flex flex-col justify-between p-2">
            {{-- Info do arquivo --}}
            <div class="text-white text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                <p class="font-semibold truncate">{{ $file->original_name }}</p>
                <p>{{ $file->size }} KB</p>
                <p class="mt-1">Pasta: {{ $file->folder->name ?? 'Sem pasta' }}</p>
            </div>

            {{-- Ações --}}
            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex justify-end space-x-1">
                <button @click="navigator.clipboard.writeText(url); showAlert('URL copiada!', 'success')" title="Copiar URL" class="p-1.5 bg-white/80 rounded-full text-gray-700 hover:bg-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                </button>
                <form action="{{ route('images.destroy', $file) }}" method="POST" onsubmit="return confirm('Tem certeza?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="Excluir" class="p-1.5 bg-red-500/80 rounded-full text-white hover:bg-red-500">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
@empty
    <p class="col-span-full text-center text-gray-500">Nenhum arquivo enviado ainda.</p>
@endforelse

                    </div>

                    {{-- Links de Paginação --}}
                    <div class="mt-8">
                        {{ $images->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = Array.from(document.querySelectorAll('.image-checkbox'));
        const selectionBar = document.getElementById('selection-bar');
        const selectedCount = document.getElementById('selected-count');
        const moveForm = document.getElementById('move-form');
        const imagesContainer = document.getElementById('move-form-images');

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
    });
</script>
</x-app-layout>
