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
</x-app-layout>