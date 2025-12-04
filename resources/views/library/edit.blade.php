<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar texto</h2>
                <p class="text-sm text-gray-600">Link público de leitura: <a href="{{ route('library.public.show', $entry->slug) }}" class="text-blue-600">{{ route('library.public.show', $entry->slug) }}</a></p>
                <p class="text-sm text-gray-600">Link público de edição: <a href="{{ route('library.public.edit', $entry->public_edit_token) }}" class="text-blue-600">{{ route('library.public.edit', $entry->public_edit_token) }}</a></p>
                <p class="text-xs text-gray-500">Compartilhe o link de leitura com o assistente (markdown puro) e o de edição + senha com o cliente.</p>
            </div>
            <a href="{{ route('library.index') }}" class="text-sm text-blue-600 font-semibold">Voltar</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-8 text-gray-900">
                    <form method="POST" action="{{ route('library.update', $entry) }}" class="space-y-6">
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
                        <div class="pt-4 mt-4 border-t border-gray-200 space-y-3">
                            <h3 class="font-semibold text-gray-800 text-sm">Edição pública</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Senha (para edição pública)</label>
                                <input type="password" name="public_edit_password" autocomplete="new-password" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Defina uma nova senha para compartilhar">
                                @error('public_edit_password')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">Deixe em branco para manter a senha atual.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="public_edit_enabled" value="0">
                                <input type="checkbox" id="public_edit_enabled" name="public_edit_enabled" value="1" {{ old('public_edit_enabled', $entry->public_edit_enabled) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600" >
                                <label for="public_edit_enabled" class="text-sm text-gray-700">Habilitar edição pública por link + senha</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
