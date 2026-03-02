@extends('layouts.agencia')

@section('content')
    @php
        $oldActiveTab = old('active_tab', request('tab', 'account'));
        $hasErrors = $errors->any();
        $reservedTemplateVariablesForJs = collect($reservedTemplateVariables ?? [])
            ->map(function ($definition, $name) {
                return [
                    'name' => (string) $name,
                    'label' => trim((string) ($definition['label'] ?? '')),
                    'sample_value' => trim((string) ($definition['sample_value'] ?? '')),
                ];
            })
            ->values();

        $customFieldsForJs = $reservedTemplateVariablesForJs->concat($customFields->map(function ($field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'sample_value' => $field->sample_value,
            ];
        }))->unique('name')->values();

        $variablePickerOptions = $customFieldsForJs;
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">WhatsApp API Cloud</h2>
            <p class="text-sm text-slate-500">Gerencie contas cloud e modelos de mensagens.</p>
        </div>
    </div>

    <div
        id="templateSubmitNotice"
        class="mb-6 hidden rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700"
    >
        Enviado para analise na Meta. Aguarde a resposta.
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
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
                {{ $accountsWithAppSecret }} de {{ $accounts->count() }} conta(s) com app_secret configurado para validação de assinatura.
            </p>

            <form method="POST" action="{{ route('agencia.whatsapp-cloud.webhook.rotate-key') }}" onsubmit="return confirm('Gerar nova chave de webhook do usuário?');">
                @csrf
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                    Gerar nova chave
                </button>
            </form>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                data-tab-button="account"
                class="rounded-lg px-4 py-2 text-sm font-semibold transition"
            >
                Conta
            </button>
            <button
                type="button"
                data-tab-button="templates"
                class="rounded-lg px-4 py-2 text-sm font-semibold transition"
            >
                Modelos de mensagens
            </button>
            <button
                type="button"
                data-tab-button="campaigns"
                class="rounded-lg px-4 py-2 text-sm font-semibold transition"
            >
                Envio em massa
            </button>
        </div>
    </div>

    <section data-tab-content="account" class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Contas Cloud</h3>
                <p class="text-sm text-slate-500">Cada usuário pode ter uma ou mais contas cloud para uso nas conexões e templates.</p>
            </div>
            <button
                type="button"
                id="openAccountModal"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Nova conta
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Conta</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Phone Number ID</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Business Account ID</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Padrão</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Conexões</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Modelos</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($accounts as $account)
                        @php
                            $conexoesList = $account->conexoes->pluck('name')->filter()->values();
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-medium text-slate-800">{{ $account->name }}</td>
                            <td class="px-5 py-4 text-slate-600 font-mono">{{ $account->phone_number_id }}</td>
                            <td class="px-5 py-4 text-slate-600 font-mono">{{ $account->business_account_id ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                @if($account->is_default)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">Sim</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">Não</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <span class="font-semibold">{{ $account->conexoes_count }}</span>
                                <span class="text-xs text-slate-400">
                                    {{ $conexoesList->isNotEmpty() ? ' • ' . \Illuminate\Support\Str::limit($conexoesList->implode(', '), 60) : '' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $account->templates_count }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                        data-open-account-edit
                                        data-id="{{ $account->id }}"
                                        data-name="{{ $account->name }}"
                                        data-phone-number-id="{{ $account->phone_number_id }}"
                                        data-business-account-id="{{ $account->business_account_id }}"
                                        data-app-id="{{ $account->app_id }}"
                                        data-is-default="{{ $account->is_default ? '1' : '0' }}"
                                    >
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('agencia.whatsapp-cloud.accounts.destroy', $account) }}" onsubmit="return confirm('Deseja excluir esta conta cloud?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-slate-500">Nenhuma conta cloud cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section data-tab-content="templates" class="hidden space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Modelos de Mensagens</h3>
                <p class="text-sm text-slate-500">Crie e sincronize modelos imediatamente com a Meta.</p>
            </div>
            <button
                type="button"
                id="openTemplateModal"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Criar modelo
            </button>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <form method="GET" action="{{ route('agencia.whatsapp-cloud.index') }}">
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountFilter">Conta</label>
                        <select id="accountFilter" name="account_id" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todas</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) $accountFilter === (string) $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="conexaoFilter">Conexão</label>
                        <select id="conexaoFilter" name="conexao_id" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todas</option>
                            @foreach($conexoes as $conexao)
                                <option value="{{ $conexao->id }}" @selected((string) $conexaoFilter === (string) $conexao->id)>{{ $conexao->name }} ({{ $conexao->cliente?->nome ?? 'Cliente' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Filtrar</button>
                        <a href="{{ route('agencia.whatsapp-cloud.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Limpar</a>
                    </div>
                </div>
            </form>

            <div class="flex flex-wrap justify-end gap-2">
                <form id="importMetaTemplatesForm" method="POST" action="{{ route('agencia.whatsapp-cloud.templates.import-meta') }}">
                    @csrf
                    <input type="hidden" name="active_tab" value="templates">
                    <input type="hidden" name="account_id" id="importMetaAccountId" value="{{ $accountFilter }}">
                    <input type="hidden" name="conexao_id" id="importMetaConexaoId" value="{{ $conexaoFilter }}">
                    <button
                        type="submit"
                        class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                        title="Importar modelos da conta selecionada na Meta"
                    >
                        Importar da Meta
                    </button>
                </form>
                <form method="POST" action="{{ route('agencia.whatsapp-cloud.templates.refresh-status-bulk') }}">
                    @csrf
                    <input type="hidden" name="active_tab" value="templates">
                    @if($accountFilter)
                        <input type="hidden" name="account_id" value="{{ $accountFilter }}">
                    @endif
                    @if($conexaoFilter)
                        <input type="hidden" name="conexao_id" value="{{ $conexaoFilter }}">
                    @endif
                    <button type="submit" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                        Atualizar status em lote
                    </button>
                </form>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Conta</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Conexão</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome do modelo</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Nome interno</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Idioma</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Categoria</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Status</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Sync</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $template)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 text-slate-700">{{ $template->account?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $template->conexao?->name ?? 'Todas' }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $template->title ?: '-' }}</td>
                            <td class="px-5 py-4 text-slate-700 font-mono">{{ $template->template_name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $template->language_code }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $template->category }}</td>
                            <td class="px-5 py-4 text-slate-600">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-700">{{ $template->status }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $template->last_synced_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('agencia.whatsapp-cloud.templates.refresh-status', $template) }}">
                                        @csrf
                                        <input type="hidden" name="active_tab" value="templates">
                                        @if($accountFilter)
                                            <input type="hidden" name="account_id" value="{{ $accountFilter }}">
                                        @endif
                                        @if($conexaoFilter)
                                            <input type="hidden" name="conexao_id" value="{{ $conexaoFilter }}">
                                        @endif
                                        <button type="submit" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                            Atualizar status
                                        </button>
                                    </form>
                                    <button
                                        type="button"
                                        class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                        data-open-template-edit
                                        data-id="{{ $template->id }}"
                                        data-account-id="{{ $template->whatsapp_cloud_account_id }}"
                                        data-conexao-id="{{ $template->conexao_id }}"
                                        data-title="{{ $template->title }}"
                                        data-template-name="{{ $template->template_name }}"
                                        data-language-code="{{ $template->language_code }}"
                                        data-category="{{ $template->category }}"
                                        data-status="{{ $template->status }}"
                                        data-body-text="{{ $template->body_text }}"
                                        data-footer-text="{{ $template->footer_text }}"
                                        data-buttons='@json($template->buttons ?? [])'
                                        data-variable-examples='@json($template->variable_examples ?? [])'
                                    >
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('agencia.whatsapp-cloud.templates.destroy', $template) }}" onsubmit="return confirm('Deseja excluir este modelo?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-6 text-center text-slate-500">Nenhum modelo de mensagem cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section data-tab-content="campaigns" class="hidden space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Envio em massa</h3>
                <p class="text-sm text-slate-500">Crie campanhas para envio em lote usando modelos aprovados da WhatsApp Cloud.</p>
            </div>
            <button
                type="button"
                id="openCampaignModal"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            >
                Nova campanha
            </button>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Campanha</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Cliente</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Conexão</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Modelo</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Modo</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Status</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Progresso</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Criada em</th>
                        <th class="px-5 py-3 text-left font-semibold uppercase tracking-wide text-xs">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($campaigns as $campaign)
                        @php
                            $campaignStatusClass = match($campaign->status) {
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                'running' => 'bg-blue-100 text-blue-700',
                                'scheduled' => 'bg-amber-100 text-amber-700',
                                'failed' => 'bg-rose-100 text-rose-700',
                                'canceled' => 'bg-slate-200 text-slate-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-800">{{ $campaign->name ?: 'Campanha #' . $campaign->id }}</p>
                                <p class="text-xs text-slate-500">#{{ $campaign->id }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $campaign->cliente?->nome ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $campaign->conexao?->name ?? '-' }}</td>
                            <td class="px-5 py-4 text-slate-700">
                                <p>{{ $campaign->template?->title ?: ($campaign->template?->template_name ?? '-') }}</p>
                                <p class="text-xs text-slate-500 font-mono">{{ $campaign->template?->template_name ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if($campaign->mode === 'scheduled')
                                    Programado
                                    <span class="block text-xs text-slate-500">{{ $campaign->scheduled_for?->setTimezone(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i') ?? '-' }}</span>
                                @else
                                    Imediato
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $campaignStatusClass }}">
                                    {{ $campaign->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                <p>Total: {{ $campaign->total_leads }}</p>
                                <p class="text-xs text-slate-500">Env: {{ $campaign->sent_count }} | Falhas: {{ $campaign->failed_count }} | Pulos: {{ $campaign->skipped_count }}</p>
                                @php
                                    $campaignTagIds = collect(data_get($campaign->filter_payload, 'tag_ids', []))
                                        ->filter(fn ($value) => is_numeric($value))
                                        ->values();
                                @endphp
                                <p class="text-xs text-slate-500">
                                    Público: {{ $campaignTagIds->isEmpty() ? 'Todos os leads do cliente' : 'Filtrado por tags (' . $campaignTagIds->count() . ')' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $campaign->created_at?->setTimezone(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-4">
                                @if(!in_array($campaign->status, ['completed', 'failed', 'canceled'], true))
                                    <form method="POST" action="{{ route('agencia.whatsapp-cloud.campaigns.cancel', $campaign) }}" onsubmit="return confirm('Deseja cancelar esta campanha?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="active_tab" value="campaigns">
                                        <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                            Cancelar
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">Sem ações</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-6 text-center text-slate-500">Nenhuma campanha criada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div id="accountModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-[640px] rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <h3 id="accountModalTitle" class="text-lg font-semibold text-slate-900">Nova conta cloud</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-account-close>x</button>
            </div>

            <form id="accountForm" method="POST" action="{{ route('agencia.whatsapp-cloud.accounts.store') }}" class="mt-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" id="accountFormMethod" value="POST">
                <input type="hidden" name="active_tab" value="account">
                <input type="hidden" name="account_editing_id" id="accountEditingId" value="{{ old('account_editing_id') }}">

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountName">Nome da conta</label>
                        <input id="accountName" name="name" type="text" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountPhoneNumberId">Phone Number ID</label>
                        <input id="accountPhoneNumberId" name="phone_number_id" type="text" inputmode="numeric" value="{{ old('phone_number_id') }}" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountBusinessId">Business Account ID</label>
                        <input id="accountBusinessId" name="business_account_id" type="text" inputmode="numeric" value="{{ old('business_account_id') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountAppId">App ID</label>
                        <input id="accountAppId" name="app_id" type="text" inputmode="numeric" value="{{ old('app_id') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountAppSecret">App Secret (opcional)</label>
                    <input id="accountAppSecret" name="app_secret" type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-slate-400">Usado para validar assinatura `X-Hub-Signature-256` do webhook.</p>
                </div>

                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountToken">Access Token</label>
                    <textarea id="accountToken" name="access_token" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('access_token') }}</textarea>
                    <p id="accountTokenHint" class="mt-1 text-xs text-slate-400">Obrigatório para criar. Em edição, preencha apenas se quiser trocar.</p>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input id="accountIsDefault" type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" @checked(old('is_default'))>
                    Definir como conta padrão
                </label>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-account-close>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="templateModal" class="fixed inset-0 z-50 hidden bg-white">
        <form id="templateForm" method="POST" action="{{ route('agencia.whatsapp-cloud.templates.store') }}" class="flex h-full flex-col">
            @csrf
            <input type="hidden" name="_method" id="templateFormMethod" value="POST">
            <input type="hidden" name="active_tab" value="templates">
            <input type="hidden" name="template_editing_id" id="templateEditingId" value="{{ old('template_editing_id') }}">
            <input type="hidden" name="template_name" id="templateGeneratedName" value="{{ old('template_name') }}">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 id="templateModalTitle" class="text-lg font-semibold text-slate-900">Criar modelo</h3>
                    <p class="text-xs text-slate-500">Nome interno Meta é gerado automaticamente a partir do nome do modelo.</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-template-close>Cancelar</button>
                    <button type="submit" data-template-submit class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar e sincronizar</button>
                </div>
            </div>

            <div class="grid min-h-0 flex-1 gap-0 lg:grid-cols-4">
                <section class="overflow-y-auto border-r border-slate-200 bg-slate-50 p-6 lg:col-span-1 space-y-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="templateAccount">Conta</label>
                        <select id="templateAccount" name="whatsapp_cloud_account_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}" @selected((string) old('whatsapp_cloud_account_id') === (string) $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="templateConexao">Conexão (opcional)</label>
                        <select id="templateConexao" name="conexao_id" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todas as conexões</option>
                            @foreach($conexoes as $conexao)
                                <option
                                    value="{{ $conexao->id }}"
                                    data-account-id="{{ $conexao->whatsapp_cloud_account_id }}"
                                    @selected((string) old('conexao_id') === (string) $conexao->id)
                                >
                                    {{ $conexao->name }} ({{ $conexao->cliente?->nome ?? 'Cliente' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="templateTitle">Nome do modelo</label>
                        <input id="templateTitle" name="title" type="text" required value="{{ old('title') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="templateCategory">Categoria</label>
                        <select id="templateCategory" name="category" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="UTILITY" @selected(old('category', 'UTILITY') === 'UTILITY')>UTILITY</option>
                            <option value="MARKETING" @selected(old('category') === 'MARKETING')>MARKETING</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="templateLanguage">Idioma</label>
                        <select id="templateLanguage" name="language_code" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="pt_BR" @selected(old('language_code', 'pt_BR') === 'pt_BR')>Português (pt_BR)</option>
                            <option value="en_US" @selected(old('language_code') === 'en_US')>English (en_US)</option>
                            <option value="es_ES" @selected(old('language_code') === 'es_ES')>Español (es_ES)</option>
                        </select>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome interno Meta (oculto)</p>
                        <p id="templateGeneratedNamePreview" class="mt-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-mono text-slate-600">-</p>
                    </div>
                </section>

                <section class="overflow-y-auto p-6 lg:col-span-2 space-y-5">
                    <div class="flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <div class="min-w-[220px] flex-1">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="variablePicker">Inserir variável</label>
                            <select id="variablePicker" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione uma variavel</option>
                                @foreach($variablePickerOptions as $field)
                                    <option value="{{ $field['name'] }}">{{ $field['name'] }}{{ !empty($field['label']) ? ' - ' . $field['label'] : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button
                            type="button"
                            id="insertVariableButton"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white"
                        >
                            Inserir variável
                        </button>
                        <a href="{{ route('agencia.campos-personalizados.index') }}" target="_blank" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">
                            Gerenciar campos
                        </a>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="templateBody">Mensagem</label>
                        <textarea id="templateBody" name="body_text" rows="8" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('body_text') }}</textarea>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="templateFooter">Rodapé (opcional)</label>
                        <textarea id="templateFooter" name="footer_text" rows="3" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('footer_text') }}</textarea>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Botões (opcional)</label>
                            <button type="button" id="addTemplateButton" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Adicionar botão</button>
                        </div>
                        <div id="templateButtonsList" class="space-y-3"></div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-semibold text-slate-900">Exemplo das variáveis</h4>
                        <p class="mt-1 text-xs text-slate-500">A Meta exige exemplos para variáveis usadas no template.</p>
                        <div id="variableExamplesContainer" class="mt-3 space-y-3"></div>
                    </div>
                </section>

                <section class="overflow-y-auto border-l border-slate-200 bg-slate-50 p-6 lg:col-span-1">
                    <h4 class="text-sm font-semibold text-slate-900">Preview da mensagem</h4>
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 text-[11px] uppercase tracking-wide text-slate-400">WhatsApp</div>
                        <div id="previewBody" class="whitespace-pre-wrap rounded-xl bg-emerald-50 p-3 text-sm text-slate-800">Digite a mensagem para visualizar.</div>
                        <div id="previewFooter" class="mt-2 text-xs text-slate-500"></div>
                        <div id="previewButtons" class="mt-3 space-y-2"></div>
                    </div>

                    <div class="mt-4 space-y-2 text-xs text-slate-600">
                        <p><span class="font-semibold text-slate-700">Categoria:</span> <span id="previewCategory">UTILITY</span></p>
                        <p><span class="font-semibold text-slate-700">Idioma:</span> <span id="previewLanguage">pt_BR</span></p>
                    </div>
                </section>
            </div>
        </form>
    </div>

    @php
        $oldCampaignTagIds = collect(old('tag_ids', []))
            ->map(fn ($value) => (string) $value)
            ->all();
    @endphp

    <div id="campaignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6">
        <div class="max-h-full w-full max-w-6xl overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Nova campanha em massa</h3>
                    <p class="text-xs text-slate-500">Escolha cliente, conexão Cloud e modelo aprovado para disparo.</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-campaign-close>x</button>
            </div>

            <form id="campaignForm" method="POST" action="{{ route('agencia.whatsapp-cloud.campaigns.store') }}" class="grid max-h-[80vh] gap-0 lg:grid-cols-3">
                @csrf
                <input type="hidden" name="active_tab" value="campaigns">

                <section class="space-y-4 overflow-y-auto border-r border-slate-200 p-6 lg:col-span-2">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignName">Nome da campanha (opcional)</label>
                            <input
                                id="campaignName"
                                name="name"
                                type="text"
                                maxlength="160"
                                value="{{ old('name') }}"
                                class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Ex.: Reengajamento Março"
                            >
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignCliente">Cliente</label>
                            <select id="campaignCliente" name="cliente_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione</option>
                                @foreach($campaignClientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected((string) old('cliente_id') === (string) $cliente->id)>
                                        {{ $cliente->nome }}
                                    </option>
                                @endforeach
                            </select>
                            <p id="campaignLeadCountHint" class="mt-1 text-xs text-slate-500">Leads elegíveis: -</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignTags">Filtrar por tags (opcional)</label>
                        <div id="campaignTagsPicker" class="mt-1">
                            <div id="campaignTagsPills" class="min-h-[44px] rounded-lg border border-slate-200 bg-white px-2 py-2"></div>
                            <div class="relative mt-2">
                                <button
                                    type="button"
                                    id="campaignTagsToggle"
                                    class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                >
                                    Selecionar tags
                                </button>
                                <div id="campaignTagsDropdown" class="absolute left-0 z-20 mt-2 hidden w-full rounded-xl border border-slate-200 bg-white shadow-xl">
                                    <div class="border-b border-slate-100 p-2">
                                        <input
                                            id="campaignTagsSearch"
                                            type="search"
                                            placeholder="Buscar tag"
                                            class="w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                    </div>
                                    <div id="campaignTagsOptions" class="max-h-56 space-y-1 overflow-auto p-2">
                                        @forelse($campaignTags as $tag)
                                            <button
                                                type="button"
                                                data-campaign-tag-option
                                                data-value="{{ $tag->id }}"
                                                data-cliente-id="{{ $tag->cliente_id ?? '' }}"
                                                data-label="{{ $tag->name }}"
                                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                                            >
                                                <span>{{ $tag->name }}{{ $tag->cliente_id ? '' : ' (global)' }}</span>
                                                <span data-campaign-tag-action class="text-[11px] font-semibold text-blue-600">Selecionar</span>
                                            </button>
                                        @empty
                                            <div class="px-3 py-2 text-xs text-slate-400">Nenhuma tag cadastrada.</div>
                                        @endforelse
                                        <div id="campaignTagsOptionsEmpty" class="hidden px-3 py-2 text-xs text-slate-400">Nenhuma tag disponível para o cliente selecionado.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <select id="campaignTags" name="tag_ids[]" multiple class="hidden">
                            @foreach($campaignTags as $tag)
                                <option
                                    value="{{ $tag->id }}"
                                    data-cliente-id="{{ $tag->cliente_id ?? '' }}"
                                    data-label="{{ $tag->name }}"
                                    @selected(in_array((string) $tag->id, $oldCampaignTagIds, true))
                                >
                                    {{ $tag->name }}{{ $tag->cliente_id ? '' : ' (global)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Sem tags selecionadas: envia para todos os leads com telefone do cliente escolhido.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignConexao">Conexão Cloud</label>
                            <select id="campaignConexao" name="conexao_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione</option>
                                @foreach($campaignConexoes as $conexao)
                                    <option
                                        value="{{ $conexao->id }}"
                                        data-cliente-id="{{ $conexao->cliente_id }}"
                                        data-account-id="{{ $conexao->whatsapp_cloud_account_id }}"
                                        @selected((string) old('conexao_id') === (string) $conexao->id)
                                    >
                                        {{ $conexao->name }} ({{ $conexao->cliente?->nome ?? 'Cliente' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignTemplate">Modelo aprovado</label>
                            <select id="campaignTemplate" name="whatsapp_cloud_template_id" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione</option>
                                @foreach($campaignTemplates as $template)
                                    <option
                                        value="{{ $template->id }}"
                                        data-account-id="{{ $template->whatsapp_cloud_account_id }}"
                                        data-conexao-id="{{ $template->conexao_id }}"
                                        data-title="{{ $template->title ?: $template->template_name }}"
                                        data-template-name="{{ $template->template_name }}"
                                        data-language="{{ $template->language_code }}"
                                        data-body="{{ $template->body_text }}"
                                        data-footer="{{ $template->footer_text }}"
                                        data-buttons='@json($template->buttons ?? [])'
                                        @selected((string) old('whatsapp_cloud_template_id') === (string) $template->id)
                                    >
                                        {{ $template->title ?: $template->template_name }} ({{ $template->language_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Variáveis do modelo</p>
                        <p class="mt-1 text-xs text-slate-600">As variáveis serão resolvidas pelos campos personalizados do lead e fallbacks existentes (nome, telefone, info).</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignAssistantContextInstructions">Instruções para o assistente (opcional)</label>
                        <textarea
                            id="campaignAssistantContextInstructions"
                            name="assistant_context_instructions"
                            rows="4"
                            maxlength="4000"
                            class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Ex.: Quando o lead responder, priorize qualificação de interesse e objetivo antes de ofertar."
                        >{{ old('assistant_context_instructions') }}</textarea>
                        <p class="mt-1 text-xs text-slate-500">Estas instruções serão adicionadas no contexto do conv_id junto com o registro do template enviado.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="md:col-span-1">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignMode">Modo de envio</label>
                            <select id="campaignMode" name="mode" required class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="immediate" @selected(old('mode', 'immediate') === 'immediate')>Imediato</option>
                                <option value="scheduled" @selected(old('mode') === 'scheduled')>Programado</option>
                            </select>
                        </div>
                        <div id="campaignScheduleWrap" class="md:col-span-1 hidden">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignScheduledFor">Data/hora</label>
                            <input
                                id="campaignScheduledFor"
                                name="scheduled_for"
                                type="datetime-local"
                                value="{{ old('scheduled_for') }}"
                                class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                        <div class="md:col-span-1">
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="campaignInterval">Intervalo entre envios (s)</label>
                            <input
                                id="campaignInterval"
                                name="interval_seconds"
                                type="number"
                                min="0"
                                max="120"
                                value="{{ old('interval_seconds', 2) }}"
                                class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-campaign-close>Cancelar</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Criar campanha</button>
                    </div>
                </section>

                <section class="space-y-4 overflow-y-auto bg-slate-50 p-6 lg:col-span-1">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Preview do modelo</h4>
                        <p class="text-xs text-slate-500">Mensagem exibida ao selecionar o modelo.</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p id="campaignPreviewTitle" class="text-sm font-semibold text-slate-800">Selecione um modelo</p>
                        <p id="campaignPreviewMeta" class="mt-1 text-xs text-slate-500">-</p>
                        <div id="campaignPreviewBody" class="mt-3 whitespace-pre-wrap rounded-lg bg-emerald-50 p-3 text-sm text-slate-800">-</div>
                        <div id="campaignPreviewFooter" class="mt-2 text-xs text-slate-500"></div>
                        <div id="campaignPreviewButtons" class="mt-3 space-y-2"></div>
                    </div>
                </section>
            </form>
        </div>
    </div>

    <template id="templateButtonRowTemplate">
        <div class="rounded-xl border border-slate-200 p-3" data-template-button-row>
            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tipo</label>
                    <select data-field="type" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="QUICK_REPLY">Resposta rápida</option>
                        <option value="URL">URL</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Texto do botão</label>
                    <input data-field="text" type="text" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end justify-end">
                    <button type="button" data-remove-button class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Remover</button>
                </div>
            </div>
            <div data-url-wrapper class="mt-3 hidden">
                <label class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">URL</label>
                <input data-field="url" type="text" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="https://exemplo.com/pedido/{codigo}">
            </div>
        </div>
    </template>

    <script>
        (function () {
            const hasErrors = @json($hasErrors);
            const oldActiveTab = @json($oldActiveTab);
            const oldAccountEditingId = @json(old('account_editing_id'));
            const oldTemplateEditingId = @json(old('template_editing_id'));
            const oldButtons = @json(old('buttons', []));
            const oldVariableExamples = @json(old('variable_examples', []));
            const customFields = @json($customFieldsForJs);
            const campaignLeadCounts = @json($campaignLeadCounts);
            const campaignLeadCountUrl = @json(route('agencia.whatsapp-cloud.campaigns.lead-count'));

            const tabButtons = document.querySelectorAll('[data-tab-button]');
            const tabContents = document.querySelectorAll('[data-tab-content]');

            const setActiveTab = (tab) => {
                tabButtons.forEach((button) => {
                    const active = button.dataset.tabButton === tab;
                    button.classList.toggle('bg-slate-900', active);
                    button.classList.toggle('text-white', active);
                    button.classList.toggle('bg-slate-100', !active);
                    button.classList.toggle('text-slate-700', !active);
                });

                tabContents.forEach((content) => {
                    content.classList.toggle('hidden', content.dataset.tabContent !== tab);
                });
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => setActiveTab(button.dataset.tabButton));
            });

            const accountModal = document.getElementById('accountModal');
            const openAccountModal = document.getElementById('openAccountModal');
            const accountCloseButtons = accountModal.querySelectorAll('[data-account-close]');
            const accountForm = document.getElementById('accountForm');
            const accountFormMethod = document.getElementById('accountFormMethod');
            const accountModalTitle = document.getElementById('accountModalTitle');
            const accountEditingId = document.getElementById('accountEditingId');
            const accountName = document.getElementById('accountName');
            const accountPhoneNumberId = document.getElementById('accountPhoneNumberId');
            const accountBusinessId = document.getElementById('accountBusinessId');
            const accountAppId = document.getElementById('accountAppId');
            const accountAppSecret = document.getElementById('accountAppSecret');
            const accountToken = document.getElementById('accountToken');
            const accountTokenHint = document.getElementById('accountTokenHint');
            const accountIsDefault = document.getElementById('accountIsDefault');

            const templateModal = document.getElementById('templateModal');
            const openTemplateModal = document.getElementById('openTemplateModal');
            const templateCloseButtons = templateModal.querySelectorAll('[data-template-close]');
            const templateForm = document.getElementById('templateForm');
            const templateFormMethod = document.getElementById('templateFormMethod');
            const templateSubmitButton = templateForm.querySelector('[data-template-submit]');
            const templateModalTitle = document.getElementById('templateModalTitle');
            const templateEditingId = document.getElementById('templateEditingId');
            const templateGeneratedName = document.getElementById('templateGeneratedName');
            const templateGeneratedNamePreview = document.getElementById('templateGeneratedNamePreview');
            const templateSubmitNotice = document.getElementById('templateSubmitNotice');

            const templateAccount = document.getElementById('templateAccount');
            const templateConexao = document.getElementById('templateConexao');
            const templateTitle = document.getElementById('templateTitle');
            const templateLanguage = document.getElementById('templateLanguage');
            const templateCategory = document.getElementById('templateCategory');
            const templateBody = document.getElementById('templateBody');
            const templateFooter = document.getElementById('templateFooter');
            const templateButtonsList = document.getElementById('templateButtonsList');
            const templateButtonRowTemplate = document.getElementById('templateButtonRowTemplate');
            const addTemplateButton = document.getElementById('addTemplateButton');
            const variablePicker = document.getElementById('variablePicker');
            const insertVariableButton = document.getElementById('insertVariableButton');
            const variableExamplesContainer = document.getElementById('variableExamplesContainer');

            const previewBody = document.getElementById('previewBody');
            const previewFooter = document.getElementById('previewFooter');
            const previewButtons = document.getElementById('previewButtons');
            const previewCategory = document.getElementById('previewCategory');
            const previewLanguage = document.getElementById('previewLanguage');

            const campaignModal = document.getElementById('campaignModal');
            const openCampaignModal = document.getElementById('openCampaignModal');
            const campaignCloseButtons = campaignModal?.querySelectorAll('[data-campaign-close]') || [];
            const campaignForm = document.getElementById('campaignForm');
            const campaignCliente = document.getElementById('campaignCliente');
            const campaignTags = document.getElementById('campaignTags');
            const campaignTagsPills = document.getElementById('campaignTagsPills');
            const campaignTagsToggle = document.getElementById('campaignTagsToggle');
            const campaignTagsDropdown = document.getElementById('campaignTagsDropdown');
            const campaignTagsSearch = document.getElementById('campaignTagsSearch');
            const campaignTagsOptions = document.getElementById('campaignTagsOptions');
            const campaignTagsOptionsEmpty = document.getElementById('campaignTagsOptionsEmpty');
            const campaignConexao = document.getElementById('campaignConexao');
            const campaignTemplate = document.getElementById('campaignTemplate');
            const campaignMode = document.getElementById('campaignMode');
            const campaignScheduleWrap = document.getElementById('campaignScheduleWrap');
            const campaignScheduledFor = document.getElementById('campaignScheduledFor');
            const campaignLeadCountHint = document.getElementById('campaignLeadCountHint');
            const campaignPreviewTitle = document.getElementById('campaignPreviewTitle');
            const campaignPreviewMeta = document.getElementById('campaignPreviewMeta');
            const campaignPreviewBody = document.getElementById('campaignPreviewBody');
            const campaignPreviewFooter = document.getElementById('campaignPreviewFooter');
            const campaignPreviewButtons = document.getElementById('campaignPreviewButtons');
            const templatesAccountFilter = document.getElementById('accountFilter');
            const templatesConexaoFilter = document.getElementById('conexaoFilter');
            const importMetaTemplatesForm = document.getElementById('importMetaTemplatesForm');
            const importMetaAccountId = document.getElementById('importMetaAccountId');
            const importMetaConexaoId = document.getElementById('importMetaConexaoId');
            let campaignLeadCountRequestId = 0;
            let templateSubmitting = false;

            let lastFocusedInput = templateBody;
            let variableExampleSeed = {};

            const openModal = (modal) => {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = (modal) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

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

            const showCopyFeedback = (button, status) => {
                if (!button) {
                    return;
                }

                if (!button.dataset.originalCopyLabel) {
                    button.dataset.originalCopyLabel = (button.textContent || '').trim() || 'Copiar';
                }

                if (button.__copyFeedbackTimeout) {
                    clearTimeout(button.__copyFeedbackTimeout);
                }

                button.classList.remove('bg-green-100', 'text-green-700', 'border-green-200', 'bg-red-100', 'text-red-700', 'border-red-200');

                if (status === 'success') {
                    button.textContent = 'Copiado!';
                    button.classList.add('bg-green-100', 'text-green-700', 'border-green-200');
                } else {
                    button.textContent = 'Erro';
                    button.classList.add('bg-red-100', 'text-red-700', 'border-red-200');
                }

                button.__copyFeedbackTimeout = window.setTimeout(() => {
                    button.textContent = button.dataset.originalCopyLabel || 'Copiar';
                    button.classList.remove('bg-green-100', 'text-green-700', 'border-green-200', 'bg-red-100', 'text-red-700', 'border-red-200');
                }, 1500);
            };

            const showTemplateSubmitFeedback = () => {
                if (templateSubmitNotice) {
                    templateSubmitNotice.classList.remove('hidden');
                    templateSubmitNotice.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                if (templateSubmitButton) {
                    templateSubmitButton.disabled = true;
                    templateSubmitButton.textContent = 'Enviando...';
                    templateSubmitButton.classList.add('cursor-not-allowed', 'opacity-70');
                }
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

                    try {
                        await copyTextToClipboard(target.value || target.textContent || '');
                        showCopyFeedback(button, 'success');
                    } catch (error) {
                        // Sem toast global nesta tela: mantém falha silenciosa.
                        showCopyFeedback(button, 'error');
                    }
                });
            });

            if (importMetaTemplatesForm) {
                importMetaTemplatesForm.addEventListener('submit', (event) => {
                    if (importMetaAccountId) {
                        importMetaAccountId.value = templatesAccountFilter?.value || '';
                    }
                    if (importMetaConexaoId) {
                        importMetaConexaoId.value = templatesConexaoFilter?.value || '';
                    }

                    if (!importMetaAccountId || !importMetaAccountId.value) {
                        event.preventDefault();
                        window.alert('Selecione uma conta no filtro para importar os modelos da Meta.');
                    }
                });
            }

            const resetAccountForm = () => {
                accountForm.action = "{{ route('agencia.whatsapp-cloud.accounts.store') }}";
                accountFormMethod.value = 'POST';
                accountModalTitle.textContent = 'Nova conta cloud';
                accountEditingId.value = '';
                accountName.value = '';
                accountPhoneNumberId.value = '';
                accountBusinessId.value = '';
                accountAppId.value = '';
                accountAppSecret.value = '';
                accountToken.value = '';
                accountToken.required = true;
                accountTokenHint.textContent = 'Obrigatório para criar. Em edição, preencha apenas se quiser trocar.';
                accountIsDefault.checked = false;
            };

            openAccountModal?.addEventListener('click', () => {
                resetAccountForm();
                openModal(accountModal);
            });

            accountCloseButtons.forEach((button) => {
                button.addEventListener('click', () => closeModal(accountModal));
            });

            accountModal.addEventListener('click', (event) => {
                if (event.target === accountModal) {
                    closeModal(accountModal);
                }
            });

            document.querySelectorAll('[data-open-account-edit]').forEach((button) => {
                button.addEventListener('click', () => {
                    resetAccountForm();
                    const id = button.dataset.id;
                    accountForm.action = `{{ url('/agencia/whatsapp-api-cloud/contas') }}/${id}`;
                    accountFormMethod.value = 'PATCH';
                    accountModalTitle.textContent = 'Editar conta cloud';
                    accountEditingId.value = id || '';
                    accountName.value = button.dataset.name || '';
                    accountPhoneNumberId.value = button.dataset.phoneNumberId || '';
                    accountBusinessId.value = button.dataset.businessAccountId || '';
                    accountAppId.value = button.dataset.appId || '';
                    accountAppSecret.value = '';
                    accountIsDefault.checked = button.dataset.isDefault === '1';
                    accountToken.value = '';
                    accountToken.required = false;
                    accountTokenHint.textContent = 'Opcional em edição. Preencha apenas se desejar substituir o token atual.';
                    openModal(accountModal);
                });
            });

            const slugifyTemplateName = (value) => {
                const normalized = (value || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase()
                    .replace(/[^a-z0-9_]+/g, '_')
                    .replace(/^_+|_+$/g, '');

                if (!normalized) {
                    return 'modelo_template';
                }

                if (/^\d/.test(normalized)) {
                    return `template_${normalized}`;
                }

                return normalized.substring(0, 255);
            };

            const updateGeneratedTemplateName = () => {
                const generated = slugifyTemplateName(templateTitle.value);
                templateGeneratedName.value = generated;
                templateGeneratedNamePreview.textContent = generated || '-';
            };

            const updateTemplateConexaoOptions = () => {
                const selectedAccountId = templateAccount.value;

                Array.from(templateConexao.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const optionAccountId = option.dataset.accountId || '';
                    const allowed = optionAccountId === '' || optionAccountId === selectedAccountId;
                    option.hidden = !allowed;
                    option.disabled = !allowed;

                    if (!allowed && option.selected) {
                        option.selected = false;
                    }
                });
            };

            const updateButtonIndexes = () => {
                const rows = templateButtonsList.querySelectorAll('[data-template-button-row]');

                rows.forEach((row, index) => {
                    const typeInput = row.querySelector('[data-field="type"]');
                    const textInput = row.querySelector('[data-field="text"]');
                    const urlInput = row.querySelector('[data-field="url"]');

                    typeInput.name = `buttons[${index}][type]`;
                    textInput.name = `buttons[${index}][text]`;
                    urlInput.name = `buttons[${index}][url]`;
                });
            };

            const attachFocusTracking = (element) => {
                element.addEventListener('focus', () => {
                    lastFocusedInput = element;
                });
            };

            const createButtonRow = (button = {}) => {
                const fragment = templateButtonRowTemplate.content.cloneNode(true);
                const row = fragment.querySelector('[data-template-button-row]');
                const typeInput = row.querySelector('[data-field="type"]');
                const textInput = row.querySelector('[data-field="text"]');
                const urlInput = row.querySelector('[data-field="url"]');
                const urlWrapper = row.querySelector('[data-url-wrapper]');
                const removeButton = row.querySelector('[data-remove-button]');

                typeInput.value = button.type || 'QUICK_REPLY';
                textInput.value = button.text || '';
                urlInput.value = button.url || '';

                const toggleUrl = () => {
                    const isUrl = typeInput.value === 'URL';
                    urlWrapper.classList.toggle('hidden', !isUrl);
                    urlInput.disabled = !isUrl;
                };

                typeInput.addEventListener('change', () => {
                    toggleUrl();
                    updateButtonIndexes();
                    renderPreview();
                    renderVariableExamples();
                });

                textInput.addEventListener('input', () => {
                    renderPreview();
                    renderVariableExamples();
                });

                urlInput.addEventListener('input', () => {
                    renderPreview();
                    renderVariableExamples();
                });

                attachFocusTracking(textInput);
                attachFocusTracking(urlInput);

                removeButton.addEventListener('click', () => {
                    row.remove();
                    updateButtonIndexes();
                    renderPreview();
                    renderVariableExamples();
                });

                toggleUrl();
                templateButtonsList.appendChild(row);
                updateButtonIndexes();
            };

            const setButtons = (buttons) => {
                templateButtonsList.innerHTML = '';

                if (Array.isArray(buttons)) {
                    buttons.forEach((button) => createButtonRow(button || {}));
                }

                updateButtonIndexes();
                renderPreview();
                renderVariableExamples();
            };

            const parseVariablesFromText = (text) => {
                const result = [];
                if (!text) {
                    return result;
                }

                const regex = /\{([a-z0-9_]+)\}/g;
                let match;

                while ((match = regex.exec(text)) !== null) {
                    const variable = match[1];
                    if (!result.includes(variable)) {
                        result.push(variable);
                    }
                }

                return result;
            };

            const collectVariablesInUse = () => {
                const variables = [];
                const pushIfMissing = (name) => {
                    if (name && !variables.includes(name)) {
                        variables.push(name);
                    }
                };

                parseVariablesFromText(templateBody.value).forEach(pushIfMissing);
                parseVariablesFromText(templateFooter.value).forEach(pushIfMissing);

                templateButtonsList.querySelectorAll('[data-template-button-row]').forEach((row) => {
                    const textValue = row.querySelector('[data-field="text"]').value;
                    const urlValue = row.querySelector('[data-field="url"]').value;
                    parseVariablesFromText(textValue).forEach(pushIfMissing);
                    parseVariablesFromText(urlValue).forEach(pushIfMissing);
                });

                return variables;
            };

            const getCustomFieldSample = (name) => {
                const field = customFields.find((item) => item.name === name);
                return field?.sample_value || '';
            };

            const renderVariableExamples = () => {
                const variables = collectVariablesInUse();
                const currentValues = {};

                variableExamplesContainer
                    .querySelectorAll('input[data-variable-example]')
                    .forEach((input) => {
                        currentValues[input.dataset.variableExample] = input.value;
                    });

                variableExamplesContainer.innerHTML = '';

                if (variables.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'text-xs text-slate-500';
                    empty.textContent = 'Nenhuma variável detectada no template.';
                    variableExamplesContainer.appendChild(empty);
                    return;
                }

                variables.forEach((variable) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'grid gap-2 md:grid-cols-3';

                    const label = document.createElement('label');
                    label.className = 'text-xs font-semibold uppercase tracking-wide text-slate-500 md:col-span-1';
                    label.textContent = `{${variable}}`;

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'md:col-span-2 rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500';
                    input.name = `variable_examples[${variable}]`;
                    input.dataset.variableExample = variable;
                    input.required = true;
                    input.value = currentValues[variable] ?? variableExampleSeed[variable] ?? getCustomFieldSample(variable) ?? '';

                    wrapper.appendChild(label);
                    wrapper.appendChild(input);
                    variableExamplesContainer.appendChild(wrapper);
                });
            };

            const renderPreview = () => {
                previewBody.textContent = templateBody.value.trim() || 'Digite a mensagem para visualizar.';
                previewFooter.textContent = templateFooter.value.trim();
                previewCategory.textContent = templateCategory.value || 'UTILITY';
                previewLanguage.textContent = templateLanguage.value || 'pt_BR';

                previewButtons.innerHTML = '';

                templateButtonsList.querySelectorAll('[data-template-button-row]').forEach((row) => {
                    const type = row.querySelector('[data-field="type"]').value;
                    const text = row.querySelector('[data-field="text"]').value;
                    const url = row.querySelector('[data-field="url"]').value;

                    if (!text.trim()) {
                        return;
                    }

                    const buttonPreview = document.createElement('div');
                    buttonPreview.className = 'rounded-lg border border-emerald-200 bg-emerald-100 px-3 py-2 text-xs text-emerald-900';
                    buttonPreview.textContent = type === 'URL' && url.trim() !== ''
                        ? `${text} (${url})`
                        : text;

                    previewButtons.appendChild(buttonPreview);
                });
            };

            const insertAtCursor = (input, text) => {
                const start = input.selectionStart ?? input.value.length;
                const end = input.selectionEnd ?? input.value.length;
                const before = input.value.slice(0, start);
                const after = input.value.slice(end);
                input.value = `${before}${text}${after}`;
                const position = start + text.length;
                input.setSelectionRange(position, position);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            };

            const resetTemplateForm = () => {
                templateForm.action = "{{ route('agencia.whatsapp-cloud.templates.store') }}";
                templateFormMethod.value = 'POST';
                templateModalTitle.textContent = 'Criar modelo';
                templateEditingId.value = '';
                templateAccount.value = '';
                templateConexao.value = '';
                templateTitle.value = '';
                templateLanguage.value = 'pt_BR';
                templateCategory.value = 'UTILITY';
                templateBody.value = '';
                templateFooter.value = '';
                templateGeneratedName.value = '';
                variableExampleSeed = {};
                setButtons([]);
                updateGeneratedTemplateName();
                updateTemplateConexaoOptions();
                renderPreview();
                renderVariableExamples();
                lastFocusedInput = templateBody;
            };

            const openTemplateCreate = () => {
                resetTemplateForm();
                openModal(templateModal);
            };

            const openTemplateEdit = (button) => {
                resetTemplateForm();

                const id = button.dataset.id;
                templateForm.action = `{{ url('/agencia/whatsapp-api-cloud/modelos') }}/${id}`;
                templateFormMethod.value = 'PATCH';
                templateModalTitle.textContent = 'Editar modelo';
                templateEditingId.value = id || '';
                templateAccount.value = button.dataset.accountId || '';
                updateTemplateConexaoOptions();
                templateConexao.value = button.dataset.conexaoId || '';
                templateTitle.value = button.dataset.title || '';
                templateLanguage.value = button.dataset.languageCode || 'pt_BR';
                templateCategory.value = button.dataset.category || 'UTILITY';
                templateBody.value = button.dataset.bodyText || '';
                templateFooter.value = button.dataset.footerText || '';

                let buttons = [];
                let examples = {};
                try {
                    buttons = JSON.parse(button.dataset.buttons || '[]');
                } catch (error) {
                    buttons = [];
                }

                try {
                    examples = JSON.parse(button.dataset.variableExamples || '{}');
                } catch (error) {
                    examples = {};
                }

                variableExampleSeed = examples || {};
                setButtons(Array.isArray(buttons) ? buttons : []);
                updateGeneratedTemplateName();
                renderPreview();
                renderVariableExamples();

                openModal(templateModal);
            };

            openTemplateModal?.addEventListener('click', openTemplateCreate);

            templateCloseButtons.forEach((button) => {
                button.addEventListener('click', () => closeModal(templateModal));
            });

            templateForm.addEventListener('submit', (event) => {
                if (templateSubmitting) {
                    event.preventDefault();
                    return;
                }

                templateSubmitting = true;
                closeModal(templateModal);
                showTemplateSubmitFeedback();
            });

            templateAccount.addEventListener('change', updateTemplateConexaoOptions);
            templateTitle.addEventListener('input', updateGeneratedTemplateName);

            [templateBody, templateFooter, templateCategory, templateLanguage].forEach((field) => {
                field.addEventListener('input', () => {
                    renderPreview();
                    renderVariableExamples();
                });
                attachFocusTracking(field);
            });

            templateCategory.addEventListener('change', renderPreview);
            templateLanguage.addEventListener('change', renderPreview);

            addTemplateButton.addEventListener('click', () => createButtonRow({ type: 'QUICK_REPLY', text: '', url: '' }));

            insertVariableButton.addEventListener('click', () => {
                const selectedVariable = variablePicker.value;
                if (!selectedVariable) {
                    return;
                }

                const target = lastFocusedInput && typeof lastFocusedInput.selectionStart === 'number'
                    ? lastFocusedInput
                    : templateBody;

                insertAtCursor(target, `{${selectedVariable}}`);
            });

            document.querySelectorAll('[data-open-template-edit]').forEach((button) => {
                button.addEventListener('click', () => openTemplateEdit(button));
            });

            const campaignDefaultPreview = () => {
                if (campaignPreviewTitle) {
                    campaignPreviewTitle.textContent = 'Selecione um modelo';
                }
                if (campaignPreviewMeta) {
                    campaignPreviewMeta.textContent = '-';
                }
                if (campaignPreviewBody) {
                    campaignPreviewBody.textContent = '-';
                }
                if (campaignPreviewFooter) {
                    campaignPreviewFooter.textContent = '';
                }
                if (campaignPreviewButtons) {
                    campaignPreviewButtons.innerHTML = '';
                }
            };

            const selectedCampaignTagIds = () => {
                if (!campaignTags) {
                    return [];
                }

                return Array.from(campaignTags.selectedOptions)
                    .map((option) => option.value)
                    .filter((value) => value !== '');
            };

            const findCampaignTagSelectOption = (value) => {
                if (!campaignTags || !value) {
                    return null;
                }

                return Array.from(campaignTags.options).find((option) => option.value === value) || null;
            };

            const renderCampaignTagPills = () => {
                if (!campaignTagsPills || !campaignTags) {
                    return;
                }

                campaignTagsPills.innerHTML = '';
                const selectedOptions = Array.from(campaignTags.selectedOptions).filter((option) => option.value !== '');

                if (selectedOptions.length === 0) {
                    const placeholder = document.createElement('span');
                    placeholder.className = 'inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-500';
                    placeholder.textContent = 'Nenhuma tag selecionada';
                    campaignTagsPills.appendChild(placeholder);
                    return;
                }

                selectedOptions.forEach((option) => {
                    const pill = document.createElement('span');
                    pill.className = 'inline-flex items-center gap-2 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700';
                    pill.textContent = option.dataset.label || option.textContent || option.value;

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.dataset.removeCampaignTag = option.value;
                    removeButton.className = 'inline-flex h-4 w-4 items-center justify-center rounded-full bg-blue-200 text-[10px] font-bold text-blue-700 hover:bg-blue-300';
                    removeButton.textContent = 'x';
                    pill.appendChild(removeButton);

                    campaignTagsPills.appendChild(pill);
                });
            };

            const syncCampaignTagOptionStates = () => {
                if (!campaignTagsOptions || !campaignTags) {
                    return;
                }

                const searchTerm = (campaignTagsSearch?.value || '').toLowerCase().trim();
                const optionButtons = campaignTagsOptions.querySelectorAll('[data-campaign-tag-option]');
                if (optionButtons.length === 0) {
                    if (campaignTagsOptionsEmpty) {
                        campaignTagsOptionsEmpty.classList.add('hidden');
                    }
                    return;
                }

                let visibleCount = 0;

                optionButtons.forEach((button) => {
                    const value = button.dataset.value || '';
                    const selectOption = findCampaignTagSelectOption(value);
                    const selected = Boolean(selectOption?.selected);
                    const allowed = Boolean(selectOption && !selectOption.hidden && !selectOption.disabled);
                    const label = (button.dataset.label || '').toLowerCase();
                    const matchesSearch = searchTerm === '' || label.includes(searchTerm);
                    const visible = allowed && matchesSearch;

                    button.classList.toggle('hidden', !visible);
                    button.classList.toggle('bg-blue-50', selected);

                    const action = button.querySelector('[data-campaign-tag-action]');
                    if (action) {
                        action.textContent = selected ? 'Remover' : 'Selecionar';
                    }

                    if (visible) {
                        visibleCount++;
                    }
                });

                if (campaignTagsOptionsEmpty) {
                    campaignTagsOptionsEmpty.classList.toggle('hidden', visibleCount > 0);
                }
            };

            const toggleCampaignTagSelection = (value) => {
                const selectOption = findCampaignTagSelectOption(value);
                if (!selectOption || selectOption.disabled || selectOption.hidden) {
                    return;
                }

                selectOption.selected = !selectOption.selected;
                renderCampaignTagPills();
                syncCampaignTagOptionStates();
                updateCampaignLeadCount();
            };

            const updateCampaignTagOptions = () => {
                if (!campaignTags || !campaignCliente) {
                    return;
                }

                const clienteId = campaignCliente.value || '';
                Array.from(campaignTags.options).forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const optionClienteId = option.dataset.clienteId || '';
                    const allowed = clienteId !== '' && (optionClienteId === '' || optionClienteId === clienteId);
                    option.hidden = !allowed;
                    option.disabled = !allowed;

                    if (!allowed && option.selected) {
                        option.selected = false;
                    }
                });

                renderCampaignTagPills();
                syncCampaignTagOptionStates();
            };

            const updateCampaignLeadCount = async () => {
                if (!campaignLeadCountHint || !campaignCliente) {
                    return;
                }

                const clienteId = campaignCliente.value || '';
                if (!clienteId) {
                    campaignLeadCountHint.textContent = 'Leads elegíveis: -';
                    return;
                }

                const tagIds = selectedCampaignTagIds();
                if (tagIds.length === 0) {
                    const count = Number(campaignLeadCounts?.[clienteId] ?? 0);
                    const safeCount = Number.isFinite(count) ? count : 0;
                    campaignLeadCountHint.textContent = `Leads elegíveis: ${safeCount}${safeCount > 10000 ? ' (acima do limite de 10000)' : ''}`;
                    return;
                }

                const requestId = ++campaignLeadCountRequestId;
                campaignLeadCountHint.textContent = 'Leads elegíveis: calculando...';

                try {
                    const params = new URLSearchParams();
                    params.set('cliente_id', clienteId);
                    tagIds.forEach((tagId) => params.append('tag_ids[]', tagId));

                    const response = await fetch(`${campaignLeadCountUrl}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (requestId !== campaignLeadCountRequestId) {
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('lead_count_request_failed');
                    }

                    const payload = await response.json();
                    const count = Number(payload?.count ?? 0);
                    const safeCount = Number.isFinite(count) ? count : 0;
                    const overLimit = Boolean(payload?.over_limit) || safeCount > 10000;
                    campaignLeadCountHint.textContent = `Leads elegíveis: ${safeCount}${overLimit ? ' (acima do limite de 10000)' : ''}`;
                } catch (error) {
                    if (requestId !== campaignLeadCountRequestId) {
                        return;
                    }

                    campaignLeadCountHint.textContent = 'Leads elegíveis: não foi possível calcular agora.';
                }
            };

            const updateCampaignConexaoOptions = () => {
                if (!campaignConexao || !campaignCliente) {
                    return;
                }

                const clienteId = campaignCliente.value || '';
                Array.from(campaignConexao.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const optionClienteId = option.dataset.clienteId || '';
                    const allowed = clienteId !== '' && optionClienteId === clienteId;
                    option.hidden = !allowed;
                    option.disabled = !allowed;

                    if (!allowed && option.selected) {
                        option.selected = false;
                    }
                });
            };

            const updateCampaignTemplateOptions = () => {
                if (!campaignConexao || !campaignTemplate) {
                    return;
                }

                const selectedConexaoOption = campaignConexao.selectedOptions?.[0];
                const selectedConexaoId = campaignConexao.value || '';
                const selectedAccountId = selectedConexaoOption?.dataset?.accountId || '';

                Array.from(campaignTemplate.options).forEach((option, index) => {
                    if (index === 0) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const optionAccountId = option.dataset.accountId || '';
                    const optionConexaoId = option.dataset.conexaoId || '';
                    const sameAccount = selectedAccountId !== '' && optionAccountId === selectedAccountId;
                    const allowedByConexao = optionConexaoId === '' || optionConexaoId === selectedConexaoId;
                    const allowed = sameAccount && allowedByConexao;

                    option.hidden = !allowed;
                    option.disabled = !allowed;

                    if (!allowed && option.selected) {
                        option.selected = false;
                    }
                });
            };

            const renderCampaignTemplatePreview = () => {
                if (!campaignTemplate) {
                    return;
                }

                const option = campaignTemplate.selectedOptions?.[0];
                if (!option || !option.value) {
                    campaignDefaultPreview();
                    return;
                }

                const title = option.dataset.title || option.dataset.templateName || 'Modelo';
                const templateName = option.dataset.templateName || '-';
                const language = option.dataset.language || '-';
                const body = option.dataset.body || '-';
                const footer = option.dataset.footer || '';

                if (campaignPreviewTitle) {
                    campaignPreviewTitle.textContent = title;
                }
                if (campaignPreviewMeta) {
                    campaignPreviewMeta.textContent = `${templateName} • ${language}`;
                }
                if (campaignPreviewBody) {
                    campaignPreviewBody.textContent = body.trim() !== '' ? body : '-';
                }
                if (campaignPreviewFooter) {
                    campaignPreviewFooter.textContent = footer.trim();
                }
                if (campaignPreviewButtons) {
                    campaignPreviewButtons.innerHTML = '';
                    let buttons = [];
                    try {
                        buttons = JSON.parse(option.dataset.buttons || '[]');
                    } catch (error) {
                        buttons = [];
                    }

                    if (Array.isArray(buttons)) {
                        buttons.forEach((button) => {
                            if (!button || typeof button !== 'object') {
                                return;
                            }

                            const text = (button.text || '').toString().trim();
                            if (text === '') {
                                return;
                            }

                            const type = (button.type || 'QUICK_REPLY').toString().toUpperCase();
                            const url = (button.url || '').toString().trim();

                            const item = document.createElement('div');
                            item.className = 'rounded-lg border border-emerald-200 bg-emerald-100 px-3 py-2 text-xs text-emerald-900';
                            item.textContent = type === 'URL' && url !== ''
                                ? `${text} (${url})`
                                : text;
                            campaignPreviewButtons.appendChild(item);
                        });
                    }
                }
            };

            const syncCampaignScheduleState = () => {
                if (!campaignMode || !campaignScheduleWrap || !campaignScheduledFor) {
                    return;
                }

                const isScheduled = campaignMode.value === 'scheduled';
                campaignScheduleWrap.classList.toggle('hidden', !isScheduled);
                campaignScheduledFor.required = isScheduled;

                if (!isScheduled) {
                    campaignScheduledFor.value = '';
                }
            };

            const resetCampaignForm = () => {
                if (!campaignForm) {
                    return;
                }

                campaignForm.reset();
                if (campaignMode) {
                    campaignMode.value = 'immediate';
                }
                if (campaignTagsSearch) {
                    campaignTagsSearch.value = '';
                }
                campaignTagsDropdown?.classList.add('hidden');
                updateCampaignTagOptions();
                updateCampaignLeadCount();
                updateCampaignConexaoOptions();
                updateCampaignTemplateOptions();
                renderCampaignTemplatePreview();
                syncCampaignScheduleState();
            };

            openCampaignModal?.addEventListener('click', () => {
                resetCampaignForm();
                openModal(campaignModal);
            });

            campaignCloseButtons.forEach((button) => {
                button.addEventListener('click', () => closeModal(campaignModal));
            });

            campaignModal?.addEventListener('click', (event) => {
                if (event.target === campaignModal) {
                    closeModal(campaignModal);
                }
            });

            campaignCliente?.addEventListener('change', () => {
                updateCampaignTagOptions();
                updateCampaignLeadCount();
                updateCampaignConexaoOptions();
                updateCampaignTemplateOptions();
                renderCampaignTemplatePreview();
            });

            campaignTagsToggle?.addEventListener('click', () => {
                if (!campaignTagsDropdown) {
                    return;
                }

                campaignTagsDropdown.classList.toggle('hidden');
                if (!campaignTagsDropdown.classList.contains('hidden')) {
                    campaignTagsSearch?.focus();
                }
            });

            campaignTagsSearch?.addEventListener('input', () => {
                syncCampaignTagOptionStates();
            });

            campaignTagsOptions?.addEventListener('click', (event) => {
                const optionButton = event.target instanceof Element
                    ? event.target.closest('[data-campaign-tag-option]')
                    : null;

                if (!optionButton) {
                    return;
                }

                const value = optionButton.dataset.value || '';
                toggleCampaignTagSelection(value);
            });

            campaignTagsPills?.addEventListener('click', (event) => {
                const removeButton = event.target instanceof Element
                    ? event.target.closest('[data-remove-campaign-tag]')
                    : null;

                if (!removeButton || !campaignTags) {
                    return;
                }

                const value = removeButton.dataset.removeCampaignTag || '';
                const option = findCampaignTagSelectOption(value);
                if (!option) {
                    return;
                }

                option.selected = false;
                renderCampaignTagPills();
                syncCampaignTagOptionStates();
                updateCampaignLeadCount();
            });

            document.addEventListener('click', (event) => {
                if (!campaignTagsDropdown || !campaignTagsToggle) {
                    return;
                }

                const target = event.target;
                if (!(target instanceof Node)) {
                    return;
                }

                const clickedInsideDropdown = campaignTagsDropdown.contains(target);
                const clickedToggle = campaignTagsToggle.contains(target);
                if (!clickedInsideDropdown && !clickedToggle) {
                    campaignTagsDropdown.classList.add('hidden');
                }
            });

            campaignConexao?.addEventListener('change', () => {
                updateCampaignTemplateOptions();
                renderCampaignTemplatePreview();
            });

            campaignTemplate?.addEventListener('change', renderCampaignTemplatePreview);
            campaignMode?.addEventListener('change', syncCampaignScheduleState);

            const allowedTabs = ['account', 'templates', 'campaigns'];
            const initialTab = allowedTabs.includes(oldActiveTab) ? oldActiveTab : 'account';
            setActiveTab(initialTab);
            updateGeneratedTemplateName();
            updateTemplateConexaoOptions();
            renderPreview();
            renderVariableExamples();
            updateCampaignTagOptions();
            updateCampaignLeadCount();
            updateCampaignConexaoOptions();
            updateCampaignTemplateOptions();
            renderCampaignTemplatePreview();
            syncCampaignScheduleState();

            if (hasErrors) {
                if (oldActiveTab === 'templates') {
                    if (oldTemplateEditingId) {
                        templateForm.action = `{{ url('/agencia/whatsapp-api-cloud/modelos') }}/${oldTemplateEditingId}`;
                        templateFormMethod.value = 'PATCH';
                        templateModalTitle.textContent = 'Editar modelo';
                        templateEditingId.value = oldTemplateEditingId;
                    }

                    variableExampleSeed = oldVariableExamples || {};
                    setButtons(Array.isArray(oldButtons) ? oldButtons : []);
                    updateGeneratedTemplateName();
                    updateTemplateConexaoOptions();
                    renderPreview();
                    renderVariableExamples();
                    openModal(templateModal);
                } else if (oldActiveTab === 'campaigns') {
                    updateCampaignTagOptions();
                    updateCampaignLeadCount();
                    updateCampaignConexaoOptions();
                    updateCampaignTemplateOptions();
                    renderCampaignTemplatePreview();
                    syncCampaignScheduleState();
                    openModal(campaignModal);
                } else {
                    if (oldAccountEditingId) {
                        accountForm.action = `{{ url('/agencia/whatsapp-api-cloud/contas') }}/${oldAccountEditingId}`;
                        accountFormMethod.value = 'PATCH';
                        accountModalTitle.textContent = 'Editar conta cloud';
                        accountToken.required = false;
                        accountTokenHint.textContent = 'Opcional em edição. Preencha apenas se desejar substituir o token atual.';
                    }
                    openModal(accountModal);
                }
            }
        })();
    </script>
@endsection
