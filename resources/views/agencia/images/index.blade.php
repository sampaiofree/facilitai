@extends('layouts.agencia')

@section('content')

    @php
        $imagesRouteBase = $imagesRouteBase ?? 'images';
        $foldersRouteBase = $foldersRouteBase ?? 'folders';
        $quotaUser = auth()->user();
        $quotaPlan = $quotaUser?->plan;
        $quotaUsedMb = (int) ($quotaUser?->storage_used_mb ?? 0);
        $quotaLimitMb = (int) ($quotaPlan?->storage_limit_mb ?? 0);
        $quotaPercent = $quotaLimitMb > 0 ? min(100, (int) round(($quotaUsedMb / $quotaLimitMb) * 100)) : 0;
        $quotaColor = $quotaLimitMb > 0
            ? ($quotaPercent > 90 ? 'bg-rose-500' : ($quotaPercent >= 70 ? 'bg-amber-400' : 'bg-emerald-500'))
            : 'bg-slate-300';
        $formatStorage = function (int $mb): string {
            if ($mb >= 1024) {
                return number_format($mb / 1024, 1, ',', '.') . ' GB';
            }
            return number_format($mb, 0, ',', '.') . ' MB';
        };
    @endphp
    <div class="grid gap-6">
        <!-- Seção de Upload -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Enviar nova mídia</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Gerencie imagens, vídeos, áudios e PDFs do seu usuário. Envie arquivos até 10MB, organize em pastas e copie o link para o assistente.
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        1. Envie os arquivos &nbsp;|&nbsp; 2. Organize em pastas &nbsp;|&nbsp; 3. Copie o link.
                    </p>
                </div>
            </div>
            <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Armazenamento</p>
                        <p class="text-xs text-slate-500">
                            @if($quotaLimitMb > 0)
                                {{ $formatStorage($quotaUsedMb) }} de {{ $formatStorage($quotaLimitMb) }} usados
                            @else
                                Nenhum plano definido
                            @endif
                        </p>
                    </div>
                    <div class="flex-1 sm:max-w-md">
                        <div class="h-2 w-full rounded-full bg-slate-100">
                            <div class="h-2 rounded-full {{ $quotaColor }}" style="width: {{ $quotaPercent }}%"></div>
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500">
                            @if($quotaLimitMb > 0)
                                {{ $quotaPercent }}% usado
                            @else
                                Selecione um plano para liberar uploads
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
<!-- Modal Upload -->
            <div id="upload-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-start justify-center z-50 overflow-y-auto py-6">
                <div class="bg-white rounded-lg shadow-lg max-w-4xl w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">Enviar mídia</h3>
                        <button type="button" id="upload-modal-close" class="text-slate-500 hover:text-slate-700">&times;</button>
                    </div>
                    <form id="upload-form" action="{{ route($imagesRouteBase . '.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-slate-700 mb-1" for="upload-images">Arquivos</label>
                                <input id="upload-images" type="file" name="images[]" multiple required accept="image/jpeg,image/png,video/mp4,application/pdf,audio/mpeg,audio/mp3" class="block w-full text-sm text-slate-700 border rounded-lg px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                                @error('images')
                                    <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                                @enderror
                                @error('images.*')
                                    <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                                @enderror
                                @error('image')
                                    <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-slate-500">Selecione um ou mais arquivos. PNG, JPG, MP4, MP3 ou PDF. Até 10MB por arquivo.</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                        <div class="flex items-center justify-between gap-2">
                                        <label class="block text-sm text-slate-700 mb-1" for="upload-folder-all">Pasta para todos (opcional)</label>
                                        <span class="text-xs text-slate-400">Opcional - pode editar depois</span>
                                    </div>
                                    <select id="upload-folder-all" name="folder_id" class="border rounded-lg px-3 py-2 text-sm w-full">
                                        <option value="">Sem pasta</option>
                                        @foreach($folders as $folder)
                                            <option value="{{ $folder->id }}" {{ old('folder_id') == $folder->id ? 'selected' : '' }}>{{ $folder->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('folder_id')
                                        <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="text-xs text-slate-600 sm:flex sm:items-end">
                                    <p>Edite título, descrição e pasta de cada arquivo na lista abaixo. A pasta escolhida aqui pode ser aplicada a todos os itens.</p>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-slate-800">Arquivos selecionados</h4>
                                    <span id="upload-files-count" class="text-xs text-slate-500">0 selecionados</span>
                                </div>
                                <div id="upload-empty" class="border border-dashed border-slate-300 rounded-lg p-4 text-sm text-slate-500 bg-slate-50">Selecione um ou mais arquivos para visualizar e editar.</div>
                                <div id="upload-list" class="space-y-3 max-h-[60vh] overflow-y-auto pr-1"></div>
                                @if($errors->has('titles.*') || $errors->has('descriptions.*') || $errors->has('folders.*'))
                                    <p class="text-red-500 text-xs mt-2">Corrija os campos inválidos antes de reenviar.</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" id="upload-cancel" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancelar</button>
                            <button id="upload-submit" type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Enviar</button>
                        </div>
                    </form>
                    <template id="folder-options-template">
                        <option value="">Sem pasta</option>
                        @foreach($folders as $folder)
                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                        @endforeach
                    </template>
                </div>
            </div>

            <div id="upload-loading" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-lg px-6 py-4 flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle><path class="opacity-75" stroke-width="4" d="M4 12a8 8 0 018-8"></path></svg>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Enviando arquivo(s)...</p>
                        <p class="text-xs text-slate-600">Isso pode levar alguns segundos para arquivos maiores.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Nova Pasta -->
            <div id="folder-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-start justify-center z-50 overflow-y-auto py-6">
                <div class="bg-white rounded-lg shadow-lg max-w-xl w-full mx-4 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 id="folder-modal-title" class="text-lg font-semibold text-slate-800">Nova pasta</h3>
                        <button type="button" id="folder-modal-close" class="text-slate-500 hover:text-slate-700">&times;</button>
                    </div>
                    <form id="folder-form" action="{{ route($foldersRouteBase . '.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="_folder_form" value="create">
                        <input type="hidden" name="_folder_id" id="folder-form-id" value="">
                        <input type="hidden" name="_method" id="folder-form-method" value="POST">
                        <input type="hidden" name="user_id" id="folder-user-id" value="{{ auth()->id() }}" data-plan-limit="{{ $quotaLimitMb }}">
                        <div>
                            <label class="block text-sm text-slate-700 mb-1" for="folder-client-id">Cliente (opcional)</label>
                            <select id="folder-client-id" name="cliente_id" class="border rounded-lg px-3 py-2 text-sm w-full" data-original-value="{{ old('cliente_id', '') }}">
                                <option value="">Sem cliente</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ (string) old('cliente_id') === (string) $client->id ? 'selected' : '' }}>
                                        {{ $client->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cliente_id')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1" for="folder-name-modal">Nome da pasta</label>
                            <input id="folder-name-modal" type="text" name="name" required maxlength="255" value="{{ old('name') }}" class="border rounded-lg px-3 py-2 text-sm w-full" placeholder="Nome da pasta">
                            @error('name')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm text-slate-700 mb-1" for="folder-storage-limit">Limite de armazenamento (MB)</label>
                            <input id="folder-storage-limit" type="number" min="0" name="storage_limit_mb" value="{{ old('storage_limit_mb', 0) }}" class="border rounded-lg px-3 py-2 text-sm w-full" placeholder="Ex: 500">
                            <p id="folder-storage-help" class="text-xs text-slate-500 mt-1">Limite do plano: {{ $quotaLimitMb }} MB</p>
                            @error('storage_limit_mb')
                                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" id="folder-cancel" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancelar</button>
                            <button id="folder-submit" type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Criar pasta</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Card Pastas -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-800">Pastas</h3>
                    <button id="open-folder-modal" type="button" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Criar pasta
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-slate-700">Cliente</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-700">Nome</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-700">Usado (MB)</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-700">Limite (MB)</th>
                                <th class="px-3 py-2 text-left font-semibold text-slate-700">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($folders as $folder)
                                <tr>
                                    <td class="px-3 py-2 align-top text-slate-700">
                                        {{ $folder->cliente?->nome ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 align-top text-slate-700">
                                        {{ $folder->name }}
                                    </td>
                                    <td class="px-3 py-2 align-top text-slate-700">
                                        {{ $folder->storage_used_mb ?? 0 }}
                                    </td>
                                    <td class="px-3 py-2 align-top text-slate-700">
                                        {{ $folder->storage_limit_mb ?? 0 }}
                                    </td>
                                    <td class="px-3 py-2 align-top">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button
                                                type="button"
                                                class="folder-edit inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-md hover:bg-blue-200"
                                                data-folder-id="{{ $folder->id }}"
                                                data-folder-name="{{ e($folder->name) }}"
                                                data-folder-user-id="{{ $folder->user_id }}"
                                                data-folder-client-id="{{ $folder->cliente_id }}"
                                                data-folder-limit="{{ $folder->storage_limit_mb ?? 0 }}"
                                                data-update-url="{{ route($foldersRouteBase . '.update', $folder) }}"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-4-9l3 3m-4-3l-7 7v3h3l7-7"></path></svg>
                                                Editar
                                            </button>
                                            <form action="{{ route($foldersRouteBase . '.destroy', $folder) }}" method="POST" onsubmit="return confirm('Tem certeza?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-100 text-red-700 text-xs font-semibold rounded-md hover:bg-red-200">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                                                    Excluir
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-slate-500">Nenhuma pasta cadastrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Card Mídias -->
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-slate-900">

                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-slate-800">Mídias</h3>
                        <div class="flex items-center gap-2">
                            <button id="open-upload-modal" type="button" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Adicionar mídia
                            </button>
                            @if($currentFolder || $selectedFolderId === 'none')
                                <a href="{{ route($imagesRouteBase . '.index') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                    Voltar
                                </a>
                            @endif
                        </div>
                    </div>

                    <form method="GET" action="{{ route($imagesRouteBase . '.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
                        <div>
                            <label for="filter-folder" class="text-sm text-slate-700">Pasta</label>
                            <select id="filter-folder" name="folder_id" class="border rounded-lg px-3 py-2 text-sm w-full">
                                <option value="" {{ empty($selectedFolderId) ? 'selected' : '' }}>Todas</option>
                                <option value="none" {{ $selectedFolderId === 'none' ? 'selected' : '' }}>Sem pasta</option>
                                @foreach($folders as $folder)
                                    <option value="{{ $folder->id }}" {{ (string)$selectedFolderId === (string)$folder->id ? 'selected' : '' }}>
                                        {{ $folder->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-client" class="text-sm text-slate-700">Cliente</label>
                            <select id="filter-client" name="cliente_id" class="border rounded-lg px-3 py-2 text-sm w-full">
                                <option value="" {{ empty($selectedClienteId) ? 'selected' : '' }}>Todos</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ (string)$selectedClienteId === (string)$client->id ? 'selected' : '' }}>
                                        {{ $client->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg">Filtrar</button>
                    </form>

                    <form id="move-form" action="{{ route($imagesRouteBase . '.move') }}" method="POST" class="mb-4">
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
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="submit" class="bg-blue-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Mover selecionados</button>
                                <button type="button" id="delete-selected" class="bg-red-600 text-white text-sm font-semibold px-4 py-2 rounded-lg">Excluir selecionados</button>
                                <button type="button" id="copy-selected" class="bg-slate-100 text-slate-800 text-sm font-semibold px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-200">Copiar todos os links</button>
                            </div>
                        </div>
                    </form>
                    <form id="delete-form" action="{{ route($imagesRouteBase . '.bulkDestroy') }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                        <div id="delete-form-images"></div>
                    </form>


                    <div id="gallery-table" class="overflow-x-auto transition">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 w-10">
                                        <span class="sr-only">Selecionar</span>
                                    </th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Nome</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Tipo</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Pasta</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Cliente</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Tamanho</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-700">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($images as $file)
                                    @php
                                        $ext = strtolower(pathinfo($file->url, PATHINFO_EXTENSION));
                                        $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']);
                                        $isAudio = in_array($ext, ['mp3']);
                                        $isPDF = in_array($ext, ['pdf']);
                                        $typeLabel = $isVideo ? 'Vídeo' : ($isAudio ? 'Áudio' : ($isPDF ? 'PDF' : 'Imagem'));
                                        $typeColor = $isVideo ? 'text-red-700 bg-red-50' : ($isAudio ? 'text-indigo-700 bg-indigo-50' : ($isPDF ? 'text-amber-700 bg-amber-50' : 'text-green-700 bg-green-50'));
                                        $displayTitle = $file->title ?: $file->original_name;
                                        $hasDescription = !empty($file->description);
                                        $shortDescription = $hasDescription ?\Illuminate\Support\Str::limit($file->description, 80) : '-';
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 align-top">
                                            <input
                                                type="checkbox"
                                                class="image-checkbox h-4 w-4 mt-1"
                                                data-image-id="{{ $file->id }}"
                                                data-url="{{ $file->url }}"
                                                data-title="{{ e($file->title ?? '') }}"
                                                data-description="{{ e($file->description ?? '') }}"
                                                data-name="{{ e($file->original_name) }}"
                                            >
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex flex-col gap-1">
                                                <span class="font-semibold text-slate-800 truncate max-w-xs" title="{{ $displayTitle }}">{{ $displayTitle }}</span>
                                                <p class="text-xs text-slate-600">
                                                    {{ $hasDescription ? $shortDescription : '-' }}
                                                </p>
                                                
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 align-top text-slate-700">
                                            <span class="inline-flex items-center gap-2 px-2 py-1 rounded-lg text-xs font-semibold {{ $typeColor }}">
                                                @if($isVideo)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-5.197-3.132A1 1 0 008 8.868v6.264a1 1 0 001.555.832l5.197-3.132a1 1 0 000-1.664z"></path></svg>
                                                @elseif($isAudio)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l10-2v13"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19a3 3 0 11-6 0 3 3 0 016 0zm10-2a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                @elseif($isPDF)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h7m-7 4h7m2 9H7a2 2 0 01-2-2V6a2 2 0 012-2h7l5 5v9a2 2 0 01-2 2z"></path></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M3 19h18M3 5v14l7-7 7 7V5"></path></svg>
                                                @endif
                                                <span>{{ $typeLabel }}</span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 align-top text-slate-700">
                                            {{ $file->folder->name ?? 'Sem pasta' }}
                                        </td>
                                        <td class="px-3 py-2 align-top text-slate-700">
                                            {{ $file->folder?->cliente?->nome ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2 align-top text-slate-700">
                                            {{ $file->size }} KB
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" class="edit-image inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-md hover:bg-blue-200" data-update-url="{{ route($imagesRouteBase . '.update', $file) }}" data-title="{{ e($file->title ?? '') }}" data-description="{{ e($file->description ?? '') }}" data-fallback-title="{{ e($file->original_name) }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-4-9l3 3m-4-3l-7 7v3h3l7-7"></path></svg>
                                                    Editar
                                                </button>
                                                <button type="button" class="copy-image-link inline-flex items-center gap-1 px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-md hover:bg-slate-200" data-url="{{ $file->url }}" data-title="{{ e($file->title ?? '') }}" data-description="{{ e($file->description ?? '') }}" title="Copiar URL">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    Link
                                                </button>
                                                <form action="{{ route($imagesRouteBase . '.destroy', $file) }}" method="POST" onsubmit="return confirm('Tem certeza?');">
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
                                        <td colspan="7" class="px-3 py-6 text-center text-slate-500">Nenhum arquivo enviado ainda.</td>
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

<div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg max-w-lg w-full mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800">Editar mídia</h3>
            <button type="button" id="edit-modal-close" class="text-slate-500 hover:text-slate-700">&times;</button>
        </div>
        <form id="edit-form" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label for="edit-title" class="block text-sm text-slate-700 mb-1">Título</label>
                <input id="edit-title" type="text" name="title" maxlength="255" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Digite o título">
            </div>
            <div class="mb-4">
                <label for="edit-description" class="block text-sm text-slate-700 mb-1">Descrição</label>
                <textarea id="edit-description" name="description" rows="4" maxlength="500" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Digite a descrição"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="edit-cancel" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200">Cancelar</button>
                <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkboxes = Array.from(document.querySelectorAll('.image-checkbox'));
        const selectionBar = document.getElementById('selection-bar');
        const galleryTable = document.getElementById('gallery-table');
        const selectedCount = document.getElementById('selected-count');
        const moveForm = document.getElementById('move-form');
        const imagesContainer = document.getElementById('move-form-images');
        const deleteForm = document.getElementById('delete-form');
        const deleteImagesContainer = document.getElementById('delete-form-images');
        const deleteSelectedBtn = document.getElementById('delete-selected');
        const copySelectedBtn = document.getElementById('copy-selected');
        const uploadModal = document.getElementById('upload-modal');
        const uploadLoading = document.getElementById('upload-loading');
        const openUploadModal = document.getElementById('open-upload-modal');
        const uploadModalClose = document.getElementById('upload-modal-close');
        const uploadCancel = document.getElementById('upload-cancel');
        const uploadForm = document.getElementById('upload-form');
        const uploadSubmit = document.getElementById('upload-submit');
        const uploadImagesInput = document.getElementById('upload-images');
        const uploadList = document.getElementById('upload-list');
        const uploadEmpty = document.getElementById('upload-empty');
        const uploadFilesCount = document.getElementById('upload-files-count');
        const uploadFolderAll = document.getElementById('upload-folder-all');
        const folderOptionsTemplate = document.getElementById('folder-options-template');
        const folderModal = document.getElementById('folder-modal');
        const openFolderModal = document.getElementById('open-folder-modal');
        const folderModalClose = document.getElementById('folder-modal-close');
        const folderCancel = document.getElementById('folder-cancel');
        const folderUserSelect = document.getElementById('folder-user-id');
        const folderUserDefault = folderUserSelect?.getAttribute('value') ?? '';
        const folderStorageHelp = document.getElementById('folder-storage-help');
        const folderStorageInput = document.getElementById('folder-storage-limit');
        const folderClientSelect = document.getElementById('folder-client-id');
        const folderForm = document.getElementById('folder-form');
        const folderFormMethod = document.getElementById('folder-form-method');
        const folderFormId = document.getElementById('folder-form-id');
        const folderNameInput = document.getElementById('folder-name-modal');
        const folderModalTitle = document.getElementById('folder-modal-title');
        const folderSubmit = document.getElementById('folder-submit');
        const folderClientOriginal = folderClientSelect?.dataset?.originalValue ?? '';
        const folderFormMode = folderForm?.querySelector('input[name="_folder_form"]');

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

        const getSelectedIds = () => checkboxes.filter(cb => cb.checked).map(cb => cb.dataset.imageId);

        const updateSelection = () => {
            const selected = getSelectedIds();
            selectedCount.textContent = selected.length;
            selectionBar.classList.toggle('hidden', selected.length === 0);
            galleryTable?.classList.toggle('bg-slate-50/70', selected.length > 0);
            galleryTable?.classList.toggle('rounded-xl', selected.length > 0);
            galleryTable?.classList.toggle('ring-1', selected.length > 0);
            galleryTable?.classList.toggle('ring-slate-200', selected.length > 0);
        };

        checkboxes.forEach(cb => cb.addEventListener('change', updateSelection));

        moveForm.addEventListener('submit', (event) => {
            const selected = getSelectedIds();
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

        deleteSelectedBtn?.addEventListener('click', () => {
            const selected = getSelectedIds();
            if (selected.length === 0) {
                if (typeof showAlert === 'function') {
                    showAlert('Selecione ao menos um item para excluir.', 'error');
                } else {
                    alert('Selecione ao menos um item para excluir.');
                }
                return;
            }

            const confirmDelete = confirm(`Excluir ${selected.length} arquivo(s)?Esta ação não pode ser desfeita.`);
            if (!confirmDelete) return;

            if (!deleteForm || !deleteImagesContainer) return;
            deleteImagesContainer.innerHTML = '';
            selected.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'images[]';
                input.value = id;
                deleteImagesContainer.appendChild(input);
            });

            deleteForm.submit();
        });

        copySelectedBtn?.addEventListener('click', async () => {
            const selectedCheckboxes = checkboxes.filter(cb => cb.checked);
            if (!selectedCheckboxes.length) {
                if (typeof showAlert === 'function') {
                    showAlert('Selecione ao menos um item para copiar.', 'error');
                } else {
                    alert('Selecione ao menos um item para copiar.');
                }
                return;
            }

            const blocks = selectedCheckboxes.map((cb) => {
                const title = (cb.dataset.title || cb.dataset.name || '').trim();
                const description = (cb.dataset.description || '').trim();
                const url = cb.dataset.url;
                const parts = [];
                if (title) parts.push(`**Título:** ${title}`);
                if (description) parts.push(`**Descrição:** ${description}`);
                if (url) parts.push(`**Link:** ${url}`);
                return parts.join('\\n');
            }).filter(Boolean);

            if (!blocks.length) return;

            try {
                await copyText(blocks.join('\\n\\n'));
                if (typeof showAlert === 'function') {
                    showAlert('Links copiados!', 'success');
                }
            } catch (e) {
                if (typeof showAlert === 'function') {
                    showAlert('Não foi possível copiar os links.', 'error');
                } else {
                    alert('Não foi possível copiar os links.');
                }
            }
        });

        const formatSize = (bytes) => {
            const kb = bytes / 1024;
            if (kb < 1024) {
                return `${kb.toFixed(1)} KB`;
            }
            return `${(kb / 1024).toFixed(1)} MB`;
        };

        const getTypeLabel = (file) => {
            const name = (file.name || '').toLowerCase();
            const type = file.type || '';
            if (type.startsWith('image/')) return 'Imagem';
            if (type.startsWith('video/')) return 'Vídeo';
            if (type.startsWith('audio/')) return 'Áudio';
            if (name.endsWith('.pdf')) return 'PDF';
            return 'Arquivo';
        };

        const createPreview = (file) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'w-24 h-16 bg-slate-100 rounded-md flex items-center justify-center overflow-hidden';
            const type = file.type || '';
            const name = (file.name || '').toLowerCase();

            if (type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'w-full h-full object-cover';
                img.onload = () => URL.revokeObjectURL(img.src);
                wrapper.appendChild(img);
                return wrapper;
            }

            const badge = document.createElement('span');
            badge.className = 'text-xs font-semibold text-slate-700 px-2 py-1 rounded-md bg-white border';
            if (type.startsWith('video/')) {
                badge.textContent = 'Vídeo';
            } else if (type.startsWith('audio/')) {
                badge.textContent = 'Áudio';
            } else if (name.endsWith('.pdf')) {
                badge.textContent = 'PDF';
            } else {
                badge.textContent = 'Arquivo';
            }
            wrapper.appendChild(badge);
            return wrapper;
        };

        const applyFolderToAll = (value) => {
            const selects = uploadList?.querySelectorAll('.file-folder') || [];
            selects.forEach(select => {
                select.value = value;
            });
        };

        const renderFileList = () => {
            if (!uploadImagesInput || !uploadList || !uploadEmpty || !uploadFilesCount) return;
            const files = Array.from(uploadImagesInput.files || []);

            uploadList.innerHTML = '';
            uploadFilesCount.textContent = `${files.length} selecionado${files.length === 1 ? '' : 's'}`;
            uploadEmpty.classList.toggle('hidden', files.length > 0);

            if (!files.length) {
                return;
            }

            const folderOptions = folderOptionsTemplate?.innerHTML || '<option value=\"\">Sem pasta</option>';

            files.forEach((file, index) => {
                const card = document.createElement('div');
                card.className = 'border border-slate-200 rounded-lg p-3 bg-slate-50';

                const header = document.createElement('div');
                header.className = 'flex items-start gap-3';

                const preview = createPreview(file);
                header.appendChild(preview);

                const meta = document.createElement('div');
                meta.className = 'flex-1 min-w-0';

                const nameRow = document.createElement('div');
                nameRow.className = 'flex items-center justify-between gap-2';

                const name = document.createElement('p');
                name.className = 'text-sm font-semibold text-slate-800 truncate';
                name.textContent = file.name || `Arquivo ${index + 1}`;

                const badge = document.createElement('span');
                badge.className = 'text-xs font-semibold text-slate-600 bg-white border rounded-md px-2 py-1';
                badge.textContent = `${getTypeLabel(file)} · ${formatSize(file.size)}`;

                nameRow.appendChild(name);
                nameRow.appendChild(badge);

                meta.appendChild(nameRow);

                const fields = document.createElement('div');
                fields.className = 'grid grid-cols-1 md:grid-cols-2 gap-3 mt-3';

                const titleWrapper = document.createElement('div');
                const titleLabel = document.createElement('label');
                titleLabel.className = 'block text-xs text-slate-600 mb-1';
                titleLabel.textContent = 'Título';
                const titleInput = document.createElement('input');
                titleInput.type = 'text';
                titleInput.name = 'titles[]';
                titleInput.maxLength = 255;
                titleInput.className = 'border rounded-lg px-3 py-2 text-sm w-full';
                titleInput.value = (file.name || '').replace(/\.[^/.]+$/, '').slice(0, 255);
                titleWrapper.appendChild(titleLabel);
                titleWrapper.appendChild(titleInput);

                const folderWrapper = document.createElement('div');
                const folderLabel = document.createElement('label');
                folderLabel.className = 'block text-xs text-slate-600 mb-1';
                folderLabel.textContent = 'Pasta (opcional)';
                const folderSelect = document.createElement('select');
                folderSelect.name = 'folders[]';
                folderSelect.className = 'border rounded-lg px-3 py-2 text-sm w-full file-folder';
                folderSelect.innerHTML = folderOptions;
                if (uploadFolderAll && uploadFolderAll.value !== undefined) {
                    folderSelect.value = uploadFolderAll.value;
                }
                folderWrapper.appendChild(folderLabel);
                folderWrapper.appendChild(folderSelect);

                const descriptionWrapper = document.createElement('div');
                descriptionWrapper.className = 'md:col-span-2';
                const descriptionLabel = document.createElement('label');
                descriptionLabel.className = 'block text-xs text-slate-600 mb-1';
                descriptionLabel.textContent = 'Descrição (opcional)';
                const descriptionInput = document.createElement('textarea');
                descriptionInput.name = 'descriptions[]';
                descriptionInput.rows = 2;
                descriptionInput.maxLength = 500;
                descriptionInput.className = 'border rounded-lg px-3 py-2 text-sm w-full';
                descriptionWrapper.appendChild(descriptionLabel);
                descriptionWrapper.appendChild(descriptionInput);

                fields.appendChild(titleWrapper);
                fields.appendChild(folderWrapper);
                fields.appendChild(descriptionWrapper);

                meta.appendChild(fields);
                header.appendChild(meta);
                card.appendChild(header);
                uploadList.appendChild(card);
            });
        };

        const resetUploadForm = () => {
            if (!uploadForm) return;
            uploadForm.reset();
            renderFileList();
        };

        const openUpload = () => {
            uploadModal.classList.remove('hidden');
            uploadModal.classList.add('flex');
        };

        const closeUpload = () => {
            resetUploadForm();
            uploadModal.classList.add('hidden');
            uploadModal.classList.remove('flex');
        };

        const updateFolderPlanLimit = () => {
            if (!folderUserSelect || !folderStorageHelp || !folderStorageInput) return;
            const limitRaw = folderUserSelect.dataset?.planLimit ?? '0';
            const limit = Number.parseInt(limitRaw, 10) || 0;
            folderStorageHelp.textContent = `Limite do plano: ${limit} MB`;
            if (limit > 0) {
                folderStorageInput.max = String(limit);
            } else {
                folderStorageInput.removeAttribute('max');
            }
        };

        const openFolder = () => {
            updateFolderPlanLimit();
            folderModal?.classList.remove('hidden');
            folderModal?.classList.add('flex');
        };

        const setFolderFormMode = (mode, payload = null) => {
            if (!folderForm || !folderFormMethod || !folderNameInput || !folderSubmit || !folderModalTitle || !folderFormId) return;

            if (mode === 'edit' && payload) {
                folderForm.action = payload.updateUrl;
                folderFormMethod.value = 'PUT';
                if (folderFormMode) folderFormMode.value = 'edit';
                folderModalTitle.textContent = 'Editar pasta';
                folderSubmit.textContent = 'Salvar';
                folderFormId.value = payload.id ?? '';
                folderNameInput.value = payload.name ?? '';
                if (folderUserSelect) {
                    folderUserSelect.value = payload.userId ?? folderUserDefault;
                }
                if (folderClientSelect) {
                    folderClientSelect.value = payload.clientId ?? '';
                }
                if (folderStorageInput) {
                    folderStorageInput.value = payload.limit ?? 0;
                }
                updateFolderPlanLimit();
                return;
            }

            folderForm.action = "{{ route($foldersRouteBase . '.store') }}";
            folderFormMethod.value = 'POST';
            if (folderFormMode) folderFormMode.value = 'create';
            folderModalTitle.textContent = 'Nova pasta';
            folderSubmit.textContent = 'Criar pasta';
            folderFormId.value = '';
            folderNameInput.value = '';
            if (folderUserSelect) {
                folderUserSelect.value = folderUserDefault;
            }
            if (folderStorageInput) {
                folderStorageInput.value = 0;
            }
            if (folderClientSelect) {
                folderClientSelect.value = folderClientOriginal;
            }
            updateFolderPlanLimit();
        };

        const closeFolder = () => {
            folderModal?.classList.add('hidden');
            folderModal?.classList.remove('flex');
        };

        openUploadModal?.addEventListener('click', openUpload);
        uploadModalClose?.addEventListener('click', closeUpload);
        uploadCancel?.addEventListener('click', closeUpload);
        uploadModal?.addEventListener('click', (event) => {
            if (event.target === uploadModal) {
                closeUpload();
            }
        });
        openFolderModal?.addEventListener('click', () => {
            setFolderFormMode('create');
            openFolder();
        });
        folderModalClose?.addEventListener('click', closeFolder);
        folderCancel?.addEventListener('click', closeFolder);
        folderModal?.addEventListener('click', (event) => {
            if (event.target === folderModal) {
                closeFolder();
            }
        });
        folderUserSelect?.addEventListener('change', updateFolderPlanLimit);
        document.querySelectorAll('.folder-edit').forEach(button => {
            button.addEventListener('click', () => {
                setFolderFormMode('edit', {
                    id: button.dataset.folderId,
                    name: button.dataset.folderName,
                    userId: button.dataset.folderUserId,
                    clientId: button.dataset.folderClientId,
                    limit: button.dataset.folderLimit,
                    updateUrl: button.dataset.updateUrl,
                });
                openFolder();
            });
        });

        uploadForm?.addEventListener('submit', (event) => {
            const files = uploadImagesInput?.files || [];
            if (!files.length) {
                event.preventDefault();
                if (typeof showAlert === 'function') {
                    showAlert('Selecione ao menos um arquivo.', 'error');
                } else {
                    alert('Selecione ao menos um arquivo.');
                }
                return;
            }
            uploadLoading?.classList.remove('hidden');
            uploadLoading?.classList.add('flex');
            if (uploadSubmit) {
                uploadSubmit.disabled = true;
                uploadSubmit.textContent = 'Enviando...';
            }
        });

        uploadImagesInput?.addEventListener('change', renderFileList);
        uploadFolderAll?.addEventListener('change', (event) => {
            applyFolderToAll(event.target.value);
        });

        @if($errors->has('image') || $errors->has('images') || $errors->has('images.*') || $errors->has('title') || $errors->has('titles.*') || $errors->has('description') || $errors->has('descriptions.*') || $errors->has('folder_id') || $errors->has('folders.*'))
            openUpload();
        @endif
        @if(old('_folder_form') === 'create')
            setFolderFormMode('create');
            openFolder();
        @endif
        @if(old('_folder_form') === 'edit' && old('_folder_id'))
            const editButton = document.querySelector(`.folder-edit[data-folder-id=\"{{ old('_folder_id') }}\"]`);
            if (editButton) {
                setFolderFormMode('edit', {
                    id: editButton.dataset.folderId,
                    name: @json(old('name', '')) || editButton.dataset.folderName,
                    userId: @json(old('user_id', '')) || editButton.dataset.folderUserId,
                    clientId: @json(old('cliente_id', '')) || editButton.dataset.folderClientId,
                    limit: @json(old('storage_limit_mb', '')) || editButton.dataset.folderLimit,
                    updateUrl: editButton.dataset.updateUrl,
                });
                openFolder();
            }
        @endif

        document.querySelectorAll('.copy-image-link').forEach(btn => {
            btn.addEventListener('click', async () => {
                const title = (btn.dataset.title || '').trim();
                const description = (btn.dataset.description || '').trim();
                const url = btn.dataset.url;
                const parts = [];
                if (title) parts.push(`**Título:** ${title}`);
                if (description) parts.push(`**Descrição:** ${description}`);
                parts.push(`**Link:** ${url}`);
                const text = parts.join('\\n');
                try {
                    await copyText(text);
                    showAlert?.('Link copiado!', 'success');
                    btn.title = 'Copiado!';
                    btn.classList.add('bg-green-100');
                    setTimeout(() => {
                        btn.title = 'Copiar URL';
                        btn.classList.remove('bg-green-100');
                    }, 1500);
                } catch (e) {
                    showAlert?.('Não foi possível copiar o link.', 'error');
                }
            });
        });

        document.querySelectorAll('.view-description').forEach(btn => {
            btn.addEventListener('click', () => {
                const title = btn.dataset.title || 'Descrição';
                const description = btn.dataset.description || '';
                alert(`${title}\n\n${description}`);
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
@endsection





