@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700" for="title">Título</label>
        <input type="text" name="title" id="title" value="{{ old('title', $lesson->title) }}" required
               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700" for="video_url">URL do vídeo (YouTube ou embed)</label>
        <input type="text" name="video_url" id="video_url" value="{{ old('video_url', $lesson->video_url) }}" required
               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <p class="text-xs text-gray-500">Aceita link do YouTube (transformamos em embed) ou um iframe src direto.</p>
    </div>

    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700" for="page_match">Página (ex: /chats ou /assistants)</label>
        <input type="text" name="page_match" id="page_match" value="{{ old('page_match', $lesson->page_match) }}" required
               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <p class="text-xs text-gray-500">Use o caminho da URL. Separe com virgula para mais de uma página. Com tipo "Prefixo" ele vale para subcaminhos (ex.: /chats/123).</p>
    </div>

    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700" for="match_type">Tipo de correspondência</label>
        <select name="match_type" id="match_type" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="prefix" @selected(old('match_type', $lesson->match_type) === 'prefix')>Prefixo (qualquer URL que comece com)</option>
            <option value="exact" @selected(old('match_type', $lesson->match_type) === 'exact')>Exata (apenas essa URL)</option>
        </select>
    </div>

    <!--<div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700" for="locale">Locale</label>
        <input type="text" name="locale" id="locale" value="{{ old('locale', $lesson->locale ?? 'pt-BR') }}" required
               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <p class="text-xs text-gray-500">Deixe "pt-BR" por padrão. No futuro, cadastre outras versões por idioma.</p>
    </div>-->

    <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700" for="position">Ordem</label>
        <input type="number" name="position" id="position" value="{{ old('position', $lesson->position) }}" min="0"
               class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <p class="text-xs text-gray-500">Menor número aparece primeiro.</p>
    </div>

    <div class="md:col-span-2 space-y-2">
        <label class="block text-sm font-medium text-gray-700" for="support_html">Texto de apoio (HTML simples)</label>
        <textarea name="support_html" id="support_html" rows="4"
                  class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('support_html', $lesson->support_html) }}</textarea>
        <p class="text-xs text-gray-500">Aceita links e listas simples. Deixe em branco se a aula for só o vídeo.</p>
    </div>

    <div class="md:col-span-2 flex items-center space-x-3">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $lesson->is_active ?? true))
               class="border-gray-300 rounded shadow-sm text-indigo-600 focus:ring-indigo-500">
        <label for="is_active" class="text-sm text-gray-700">Aula ativa</label>
    </div>
</div>

<div class="mt-6 flex items-center justify-end space-x-3">
    <a href="{{ route('admin.lessons.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancelar</a>
    <button type="submit"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 transition ease-in-out duration-150">
        Salvar
    </button>
</div>
