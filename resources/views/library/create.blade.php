<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Novo texto</h2>
            <a href="{{ route('library.index') }}" class="text-sm text-blue-600 font-semibold">Voltar</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('library.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                            <input type="text" name="title" value="{{ old('title') }}" required maxlength="255" class="w-full border rounded-lg px-3 py-2 text-sm">
                            @error('title')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Conteúdo (Markdown, até 20k caracteres)</label>
                            <textarea name="content" required maxlength="20000" rows="12" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('content') }}</textarea>
                            @error('content')
                                <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="pt-4 mt-4 border-t border-gray-200 space-y-3">
                            <h3 class="font-semibold text-gray-800 text-sm">Edição pública</h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Senha (para edição pública)</label>
                                <input type="password" name="public_edit_password" autocomplete="new-password" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="Defina uma senha para compartilhar">
                                @error('public_edit_password')
                                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">Use esta senha junto com o link público para permitir edições.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="public_edit_enabled" value="0">
                                <input type="checkbox" id="public_edit_enabled" name="public_edit_enabled" value="1" {{ old('public_edit_enabled', true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600">
                                <label for="public_edit_enabled" class="text-sm text-gray-700">Habilitar edição pública por link + senha</label>
                            </div>
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
