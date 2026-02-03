@extends('layouts.cliente')

@section('title', 'Conexões')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Conexões</h2>
            <p class="text-sm text-slate-500">Visualize e conecte suas instâncias.</p>
        </div>
    </div>

    @if($conexoes->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-500">
            Nenhuma conexão cadastrada para sua conta.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($conexoes as $conexao)
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $conexao->name ?? 'Conexão' }}</p>
                            <p class="text-xs text-slate-500">Phone: {{ $conexao->phone ?? '-' }}</p>
                        </div>
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold transition {{ $conexao->status === 'connected' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}"
                            data-conexao-status
                            data-conexao-id="{{ $conexao->id }}"
                        >
                            {{ $conexao->status ?? 'pendente' }}
                        </span>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <button
                            type="button"
                            class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            data-conexao-connect
                            data-conexao-id="{{ $conexao->id }}"
                            data-can-connect="{{ $conexao->whatsappApi?->slug === 'uazapi' ? '1' : '0' }}"
                            {{ $conexao->whatsappApi?->slug === 'uazapi' ? '' : 'disabled' }}
                        >Conectar</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div id="conexaoConnectModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[520px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Conectar WhatsApp</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-connect-close>x</button>
            </div>

            <div class="mt-6 flex flex-col items-center gap-4">
                <div id="connectSpinner" class="flex items-center gap-3 text-slate-600">
                    <span class="inline-flex h-6 w-6 animate-spin rounded-full border-2 border-slate-200 border-t-blue-500"></span>
                    <span>Aguarde...</span>
                </div>
                <img id="connectQrCode" class="hidden h-56 w-56 rounded-lg border border-slate-200 object-contain" alt="QR Code">
                <p id="connectPaircodeText" class="text-sm text-slate-600"></p>
                <p id="connectErrorText" class="text-sm font-semibold text-rose-600"></p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const statusElements = Array.from(document.querySelectorAll('[data-conexao-status]'));
        const connectButtons = Array.from(document.querySelectorAll('[data-conexao-connect]'));
        const baseUrl = "{{ url('/cliente/conexoes') }}";
        const statusUrl = (id) => `${baseUrl}/${id}/status`;
        const connectUrl = (id) => `${baseUrl}/${id}/connect`;

        const connectModal = document.getElementById('conexaoConnectModal');
        const connectCloseBtn = connectModal?.querySelector('[data-connect-close]');
        const connectSpinner = document.getElementById('connectSpinner');
        const connectQrCode = document.getElementById('connectQrCode');
        const connectPaircodeText = document.getElementById('connectPaircodeText');
        const connectErrorText = document.getElementById('connectErrorText');
        let connectStatusTimer = null;
        let connectRefreshTimer = null;
        let connectStatusAttempts = 0;
        let connectRefreshAttempts = 0;
        const maxStatusAttempts = 10;
        const maxConnectAttempts = 10;
        let currentConnectId = null;

        const openConnectModal = () => {
            if (!connectModal) return;
            connectModal.classList.remove('hidden');
            connectModal.classList.add('flex');
        };

        const closeConnectModal = () => {
            if (!connectModal) return;
            connectModal.classList.add('hidden');
            connectModal.classList.remove('flex');
            currentConnectId = null;
        };

        const resetConnectModal = () => {
            connectErrorText.textContent = '';
            connectPaircodeText.textContent = '';
            connectQrCode.classList.add('hidden');
            connectQrCode.removeAttribute('src');
            connectSpinner.classList.remove('hidden');
        };

        const stopConnectTimers = () => {
            if (connectStatusTimer) {
                clearInterval(connectStatusTimer);
                connectStatusTimer = null;
            }
            if (connectRefreshTimer) {
                clearInterval(connectRefreshTimer);
                connectRefreshTimer = null;
            }
            connectStatusAttempts = 0;
            connectRefreshAttempts = 0;
        };

        const showConnectError = (message) => {
            connectErrorText.textContent = message || 'Erro ao conectar.';
            connectSpinner.classList.add('hidden');
        };

        const applyStatusUpdate = (id, status) => {
            const el = statusElements.find(e => e.dataset.conexaoId === String(id));
            const normalized = (status || '').toString().trim().toLowerCase();
            if (el) {
                el.textContent = status;
                const isConnected = normalized === 'connected';
                el.classList.toggle('bg-emerald-100', isConnected);
                el.classList.toggle('text-emerald-700', isConnected);
                el.classList.toggle('bg-slate-100', !isConnected);
                el.classList.toggle('text-slate-600', !isConnected);
            }
            const btn = connectButtons.find(e => e.dataset.conexaoId === String(id));
            if (btn) {
                const show = normalized !== 'connected' && btn.dataset.canConnect === '1';
                btn.classList.toggle('hidden', !show);
            }
        };

        const fetchStatusForConnect = async () => {
            if (!currentConnectId) return;
            connectStatusAttempts += 1;
            if (connectStatusAttempts > maxStatusAttempts) {
                stopConnectTimers();
                showConnectError('Limite de tentativas atingido. Tente novamente.');
                return;
            }
            try {
                const response = await fetch(statusUrl(currentConnectId), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json();
                const status = payload?.status;
                if (status) {
                    applyStatusUpdate(currentConnectId, status);
                }
                if (status === 'connected') {
                    stopConnectTimers();
                    closeConnectModal();
                }
            } catch (_) {
                // ignora erro pontual
            }
        };

        const callInstanceConnect = async () => {
            if (!currentConnectId) return;
            connectRefreshAttempts += 1;
            if (connectRefreshAttempts > maxConnectAttempts) {
                stopConnectTimers();
                showConnectError('Limite de tentativas atingido. Tente novamente.');
                return;
            }
            try {
                const response = await fetch(connectUrl(currentConnectId), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                });
                const payload = await response.json();
                if (!response.ok || payload?.error) {
                    showConnectError(payload?.message || 'Erro ao conectar.');
                    return;
                }

                if (!payload.qrcode && !payload.paircode) {
                    showConnectError(payload?.message || 'Resposta sem QR code ou código.');
                    return;
                }

                connectSpinner.classList.add('hidden');
                connectErrorText.textContent = '';

                if (payload.qrcode) {
                    connectQrCode.src = payload.qrcode;
                    connectQrCode.classList.remove('hidden');
                }
                if (payload.paircode) {
                    connectPaircodeText.textContent = `Seu cliente também pode conectar com o código ${payload.paircode}.`;
                }
            } catch (_) {
                showConnectError('Erro ao conectar. Tente novamente.');
            }
        };

        connectButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (button.dataset.canConnect !== '1') return;
                currentConnectId = button.dataset.conexaoId;
                stopConnectTimers();
                resetConnectModal();
                openConnectModal();
                callInstanceConnect();
                connectStatusTimer = setInterval(fetchStatusForConnect, 15000);
                connectRefreshTimer = setInterval(callInstanceConnect, 20000);
            });
        });

        connectCloseBtn?.addEventListener('click', () => {
            stopConnectTimers();
            closeConnectModal();
        });

        connectModal?.addEventListener('click', (event) => {
            if (event.target === connectModal) {
                stopConnectTimers();
                closeConnectModal();
            }
        });

        const fetchStatus = async (element) => {
            const id = element.dataset.conexaoId;
            if (!id) return;
            element.textContent = 'Atualizando...';
            try {
                const response = await fetch(statusUrl(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const payload = await response.json();
                if (payload?.status) {
                    element.textContent = payload.status;
                    applyStatusUpdate(id, payload.status);
                } else {
                    element.textContent = payload?.message ?? 'erro';
                }
            } catch (_) {
                element.textContent = 'erro';
            }
        };

        statusElements.forEach(element => {
            applyStatusUpdate(element.dataset.conexaoId, element.textContent);
            fetchStatus(element);
        });
    })();
</script>
@endpush
