<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar texto</h2>
                <p class="text-sm text-gray-600">Link público: <a href="{{ route('library.public.show', $entry->slug) }}" class="text-blue-600">{{ route('library.public.show', $entry->slug) }}</a></p>
            </div>
            <a href="{{ route('library.index') }}" class="text-sm text-blue-600 font-semibold">Voltar</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('library.update', $entry) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                            <input type="text" name="title" value="{{ old('title', $entry->title) }}" required maxlength="255" class="w-full border rounded-lg px-3 py-2 text-sm">
                            @error('title')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Conteúdo (Markdown, até 20k caracteres)</label>
                            <textarea name="content" required maxlength="20000" rows="12" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('content', $entry->content) }}</textarea>
                            @error('content')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg">Salvar</button>
                            <a href="{{ route('library.index') }}" class="text-sm text-gray-700">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
