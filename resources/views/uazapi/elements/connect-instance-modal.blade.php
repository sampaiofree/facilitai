<div id="connectInstanceModal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/40 backdrop-blur px-4" aria-hidden="true">
    <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 id="connect-instance-title" class="text-lg font-semibold text-gray-800">Conectar instancia</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" data-modal-close="true" aria-label="Fechar">x</button>
        </div>

        <form
            method="POST"
            action="#"
            id="connectInstanceForm"
            data-action-template="{{ route('uazapi.instances.connect', ['instance' => '__INSTANCE_ID__']) }}"
            data-status-template="{{ route('uazapi.instances.status', ['instance' => '__INSTANCE_ID__']) }}"
            class="mt-4 space-y-4"
        >
            @csrf

            <div>
                <label for="connect-mode" class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Tipo de conexao</label>
                <select
                    id="connect-mode"
                    name="connect_mode"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="qrcode">QR code</option>
                    <option value="paircode">Codigo de pareamento</option>
                </select>
            </div>

            <div id="connect-phone-field" class="hidden">
                <label for="connect-phone" class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Telefone</label>
                <input
                    id="connect-phone"
                    name="phone"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="off"
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                <p class="mt-1 text-xs text-rose-600" id="connect-phone-error"></p>
            </div>

            <div id="connect-result" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                <img id="connect-qr-code" class="hidden w-60 rounded-lg border border-gray-200 bg-white p-2" alt="QR Code">
                <p class="mt-3 text-sm text-gray-600" id="connect-paircode-text"></p>
                <p class="text-sm text-gray-600" id="connect-status-message"></p>
            </div>

            <p class="text-xs text-gray-500">Em caso de erro na conexao, atualize a pagina e tente conectar novamente.</p>

            <div class="flex items-center justify-end gap-3">
                <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" data-modal-close="true">Cancelar</button>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Conectar</button>
            </div>
        </form>
    </div>
</div>
