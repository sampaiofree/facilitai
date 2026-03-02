@extends('layouts.agencia')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">WhatsApp API Cloud</h2>
            <p class="text-sm text-slate-500">Configuração do webhook único por usuário.</p>
        </div>
        <a
            href="{{ route('agencia.whatsapp-cloud.index') }}"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Ir para Contas
        </a>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Webhook do Usuário</h3>
                <p class="text-xs text-slate-500">Use este webhook único para todas as contas Cloud do usuário.</p>
            </div>
        </div>

        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            <div class="rounded-lg border border-slate-200 p-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Callback URL</p>
                <div class="mt-2 flex items-center gap-2">
                    <input
                        id="webhookUrlValue"
                        type="text"
                        readonly
                        value="{{ $webhookUrl }}"
                        class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs font-mono text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    <button type="button" data-copy-target="webhookUrlValue" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Copiar</button>
                </div>
            </div>
            <div class="rounded-lg border border-slate-200 p-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Verify Token</p>
                <div class="mt-2 flex items-center gap-2">
                    <input
                        id="webhookVerifyTokenValue"
                        type="text"
                        readonly
                        value="{{ $userWebhookVerifyToken }}"
                        class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs font-mono text-slate-700 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                    <button type="button" data-copy-target="webhookVerifyTokenValue" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Copiar</button>
                </div>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-slate-500">
                {{ $accountsWithAppSecret }} de {{ $accountsCount }} conta(s) com app_secret configurado para validação de assinatura.
            </p>

            <form method="POST" action="{{ route('agencia.whatsapp-cloud.webhook.rotate-key') }}" onsubmit="return confirm('Gerar nova chave de webhook do usuário?');">
                @csrf
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                    Gerar nova chave
                </button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const copyTextToClipboard = async (text) => {
                if (!text) {
                    return;
                }

                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            };

            document.querySelectorAll('[data-copy-target]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const targetId = button.dataset.copyTarget;
                    if (!targetId) {
                        return;
                    }

                    const target = document.getElementById(targetId);
                    if (!target) {
                        return;
                    }

                    await copyTextToClipboard(target.value || target.textContent || '');
                });
            });
        })();
    </script>
@endsection
