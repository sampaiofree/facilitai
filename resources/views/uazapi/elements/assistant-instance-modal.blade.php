<div id="assistantInstanceModal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/40 backdrop-blur px-4" aria-hidden="true">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 id="assistant-instance-title" class="text-lg font-semibold text-gray-800">Vincular assistente</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" data-modal-close="true" aria-label="Fechar">x</button>
        </div>

        <form
            method="POST"
            action="#"
            id="assistantInstanceForm"
            data-route-template="{{ route('uazapi.instances.assignAssistant', ['instance' => '__INSTANCE_ID__']) }}"
            class="mt-4 space-y-4"
        >
            @csrf

            <div>
                @if($assistants->isNotEmpty())
                    <label for="assistantSelect" class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Assistente</label>
                    <select
                        name="assistant_id"
                        id="assistantSelect"
                        required
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="" disabled selected>Selecione um assistente</option>
                        @foreach($assistants as $assistant)
                            <option value="{{ $assistant->id }}">{{ $assistant->name }}</option>
                        @endforeach
                    </select>
                @else
                    <p class="text-sm text-gray-500">Nenhum assistente disponivel.</p>
                @endif
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" data-modal-close="true">Cancelar</button>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</div>
