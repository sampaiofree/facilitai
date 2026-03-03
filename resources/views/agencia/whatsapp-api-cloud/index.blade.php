@extends('layouts.agencia')

@section('content')
    @php
        $oldActiveTab = old('active_tab', request('tab', 'templates'));
        $hasErrors = $errors->any();
        $reservedTemplateVariablesForJs = collect($reservedTemplateVariables ?? [])
            ->map(function ($definition, $name) {
                return [
                    'name' => (string) $name,
                    'label' => trim((string) ($definition['label'] ?? '')),
                    'sample_value' => trim((string) ($definition['sample_value'] ?? '')),
                    'cliente_id' => null,
                ];
            })
            ->values();

        $customFieldsForJs = $reservedTemplateVariablesForJs->concat($customFields->map(function ($field) {
            return [
                'name' => $field->name,
                'label' => $field->label,
                'sample_value' => $field->sample_value,
                'cliente_id' => $field->cliente_id ? (int) $field->cliente_id : null,
            ];
        }))->unique('name')->values();

        $variablePickerOptions = $customFieldsForJs;
        $oldCampaignTemplateBindings = old('template_variable_bindings', []);
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

    <div class="grid gap-6 lg:grid-cols-3">
        <aside class="lg:col-span-1 space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Contas Cloud</h3>
                        <p class="text-xs text-slate-500">Selecione uma conta para abrir os detalhes.</p>
                    </div>
                    <button
                        type="button"
                        id="openAccountModal"
                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        Nova conta
                    </button>
                </div>

                <div class="mt-4 space-y-2">
                    @forelse($accounts as $account)
                        <a
                            href="{{ route('agencia.whatsapp-cloud.index', ['account_id' => $account->id, 'tab' => request('tab', 'templates')]) }}"
                            class="block rounded-lg border px-3 py-3 transition {{ (int) $accountFilter === (int) $account->id ? 'border-blue-300 bg-blue-50' : 'border-slate-200 hover:bg-slate-50' }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-800">{{ $account->name }}</p>
                                @if($account->is_default)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Padrão</span>
                                @endif
                            </div>
                            <p class="mt-1 text-[11px] font-mono text-slate-500">{{ $account->phone_number_id }}</p>
                            <p class="mt-2 text-[11px] text-slate-500">Conexões: {{ $account->conexoes_count }} • Modelos: {{ $account->templates_count }}</p>
                        </a>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-4 text-xs text-slate-500">
                            Nenhuma conta cloud cadastrada.
                        </div>
                    @endforelse
                </div>
            </div>
        </aside>

        <section class="lg:col-span-2 space-y-4">
            @if(!$selectedAccount)
                <div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-900">Selecione uma conta para continuar</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Use a lista lateral para selecionar uma conta com <code>?account_id=</code>. Os modelos e campanhas serão exibidos somente da conta selecionada.
                    </p>
                    @if($accounts->isEmpty())
                        <button
                            type="button"
                            id="openAccountModalEmptyState"
                            class="mt-4 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Criar primeira conta
                        </button>
                    @endif
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $selectedAccount->name }}</h3>
                            <p class="mt-1 text-xs font-mono text-slate-500">Phone Number ID: {{ $selectedAccount->phone_number_id }}</p>
                            <p class="mt-1 text-xs font-mono text-slate-500">Business Account ID: {{ $selectedAccount->business_account_id ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-500">Conexões: {{ $selectedAccount->conexoes_count }} • Modelos: {{ $selectedAccount->templates_count }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg bg-indigo-500 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-600"
                                data-open-account-edit
                                data-id="{{ $selectedAccount->id }}"
                                data-name="{{ $selectedAccount->name }}"
                                data-phone-number-id="{{ $selectedAccount->phone_number_id }}"
                                data-business-account-id="{{ $selectedAccount->business_account_id }}"
                                data-app-id="{{ $selectedAccount->app_id }}"
                                data-is-default="{{ $selectedAccount->is_default ? '1' : '0' }}"
                            >
                                Editar conta
                            </button>
                            <form method="POST" action="{{ route('agencia.whatsapp-cloud.accounts.destroy', $selectedAccount) }}" onsubmit="return confirm('Deseja excluir esta conta cloud e a conexão vinculada?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg bg-rose-500 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-600">
                                    Excluir conta + conexão
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
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

                <section data-tab-content="templates" class="space-y-4">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Modelos de Mensagens</h3>
                            <p class="text-sm text-slate-500">Crie e sincronize modelos imediatamente com a Meta.</p>
                        </div>
                        <input type="hidden" id="accountFilter" value="{{ $accountFilter }}">
                        <input type="hidden" id="conexaoFilter" value="">
                        <div class="flex flex-wrap items-center gap-2">
                            <form id="importMetaTemplatesForm" data-template-action-form method="POST" action="{{ route('agencia.whatsapp-cloud.templates.import-meta') }}">
                                @csrf
                                <input type="hidden" name="active_tab" value="templates">
                                <input type="hidden" name="account_id" id="importMetaAccountId" value="{{ $accountFilter }}">
                                <input type="hidden" name="conexao_id" id="importMetaConexaoId" value="">
                                <button
                                    type="submit"
                                    class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                    title="Atualizar modelos da conta selecionada na Meta"
                                >
                                    Atualizar
                                </button>
                            </form>
                            <button
                                type="button"
                                id="openTemplateModal"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                            >
                                Criar modelo
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
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
                            <tbody id="templatesTableBody" class="divide-y divide-slate-100">
                                @forelse($templates as $template)
                                    <tr class="hover:bg-slate-50" data-template-row data-account-id="{{ $template->whatsapp_cloud_account_id }}" data-conexao-id="{{ $template->conexao_id ?? '' }}">
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
                                                <form data-template-action-form method="POST" action="{{ route('agencia.whatsapp-cloud.templates.refresh-status', $template) }}">
                                                    @csrf
                                                    <input type="hidden" name="active_tab" value="templates">
                                                    <input type="hidden" name="account_id" value="{{ $accountFilter }}">
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
                                    <tr data-template-empty-server>
                                        <td colspan="8" class="px-5 py-6 text-center text-slate-500">Nenhum modelo de mensagem cadastrado para esta conta.</td>
                                    </tr>
                                @endforelse
                                <tr id="templatesNoResultsRow" class="hidden">
                                    <td colspan="8" class="px-5 py-6 text-center text-slate-500">Nenhum modelo encontrado.</td>
                                </tr>
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
                            class="rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm {{ !empty($canCreateCampaign) ? 'bg-blue-600 hover:bg-blue-700' : 'cursor-not-allowed bg-slate-400' }}"
                            @disabled(empty($canCreateCampaign))
                        >
                            Nova campanha
                        </button>
                    </div>
                    @if(empty($canCreateCampaign))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Para criar campanha, vincule uma conexão Cloud a esta conta em <a href="{{ route('agencia.conexoes.index') }}" class="font-semibold underline">Conexões</a>.
                        </div>
                    @endif

                    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                        <table class="min-w-[1100px] text-sm">
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
                                                $campaignTagIncludeIds = collect(data_get($campaign->filter_payload, 'tags.include', data_get($campaign->filter_payload, 'tag_ids', [])))
                                                    ->filter(fn ($value) => is_numeric($value))
                                                    ->values();
                                                $campaignTagExcludeIds = collect(data_get($campaign->filter_payload, 'tags.exclude', []))
                                                    ->filter(fn ($value) => is_numeric($value))
                                                    ->values();
                                            @endphp
                                            <p class="text-xs text-slate-500">
                                                Público:
                                                @if($campaignTagIncludeIds->isEmpty() && $campaignTagExcludeIds->isEmpty())
                                                    Todos os leads do cliente
                                                @else
                                                    @if($campaignTagIncludeIds->isNotEmpty())
                                                        é ({{ $campaignTagIncludeIds->count() }})
                                                    @endif
                                                    @if($campaignTagIncludeIds->isNotEmpty() && $campaignTagExcludeIds->isNotEmpty())
                                                        •
                                                    @endif
                                                    @if($campaignTagExcludeIds->isNotEmpty())
                                                        não é ({{ $campaignTagExcludeIds->count() }})
                                                    @endif
                                                @endif
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
                                        <td colspan="9" class="px-5 py-6 text-center text-slate-500">Nenhuma campanha criada para esta conta.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </section>
    </div>

    <div id="accountModal" class="fixed inset-0 hidden items-center justify-center bg-black/40 backdrop-blur">
        <div class="w-full max-w-4xl max-h-[92vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
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

                <section id="accountConnectionSection" class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Conexão Cloud vinculada</h4>
                        <p class="text-xs text-slate-500">Ao criar a conta, uma conexão será criada automaticamente.</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountConexaoName">Nome da conexão (opcional)</label>
                        <input id="accountConexaoName" name="conexao_name" type="text" value="{{ old('conexao_name') }}" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Cloud - Minha Conta">
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountConnectionCliente">Cliente</label>
                            <select id="accountConnectionCliente" name="cliente_id" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                @foreach($accountConnectionClientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected((string) old('cliente_id') === (string) $cliente->id)>{{ $cliente->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountConnectionCredential">Credencial</label>
                            <select id="accountConnectionCredential" name="credential_id" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                @foreach($accountConnectionCredentials as $credential)
                                    <option value="{{ $credential->id }}" @selected((string) old('credential_id') === (string) $credential->id)>{{ $credential->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountConnectionAssistant">Assistente</label>
                            <select id="accountConnectionAssistant" name="assistant_id" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                @foreach($accountConnectionAssistants as $assistant)
                                    <option value="{{ $assistant->id }}" @selected((string) old('assistant_id') === (string) $assistant->id)>{{ $assistant->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500" for="accountConnectionModel">Modelo IA</label>
                            <select id="accountConnectionModel" name="model" class="mt-1 w-full rounded-lg border-slate-200 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                @foreach($accountConnectionModels as $model)
                                    <option value="{{ $model->id }}" @selected((string) old('model') === (string) $model->id)>{{ $model->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($accountConnectionClientes->isEmpty() || $accountConnectionCredentials->isEmpty() || $accountConnectionAssistants->isEmpty() || $accountConnectionModels->isEmpty())
                        <p class="text-xs text-rose-600">
                            Para criar conta + conexão, cadastre pelo menos um cliente, uma credencial, um assistente e um modelo IA.
                        </p>
                    @endif
                </section>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-account-close>Cancelar</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="templateModal" class="fixed inset-0 z-50 hidden bg-white">
        <form id="templateForm" method="POST" action="{{ route('agencia.whatsapp-cloud.templates.store') }}" class="flex h-full w-full flex-col">
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
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conta</label>
                        <p class="mt-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                            {{ $selectedAccount?->name ?? 'Conta não selecionada' }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conexão vinculada</label>
                        <p class="mt-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                            {{ $selectedCloudConexao?->name ?? 'Nenhuma conexão Cloud vinculada' }}
                        </p>
                    </div>

                    <select id="templateAccount" name="whatsapp_cloud_account_id" required class="hidden">
                        <option value="{{ $selectedAccount?->id }}" selected>{{ $selectedAccount?->name }}</option>
                    </select>

                    <select id="templateConexao" name="conexao_id" class="hidden">
                        <option value="">Todas as conexões</option>
                        @foreach($conexoes as $conexao)
                            <option
                                value="{{ $conexao->id }}"
                                data-account-id="{{ $conexao->whatsapp_cloud_account_id }}"
                                @selected((string) old('conexao_id', $selectedCloudConexao?->id) === (string) $conexao->id)
                            >
                                {{ $conexao->name }} ({{ $conexao->cliente?->nome ?? 'Cliente' }})
                            </option>
                        @endforeach
                    </select>

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
        $oldCampaignTagIncludeIds = collect(old('tag_include_ids', old('tag_ids', [])))
            ->map(fn ($value) => (string) $value)
            ->all();
        $oldCampaignTagExcludeIds = collect(old('tag_exclude_ids', []))
            ->map(fn ($value) => (string) $value)
            ->all();
    @endphp

    <div id="campaignModal" class="fixed inset-0 z-50 hidden items-start justify-center overflow-y-auto bg-black/50 px-4 py-6">
        <div class="my-2 flex h-[min(920px,92vh)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Nova campanha em massa</h3>
                    <p class="text-xs text-slate-500">Cliente e conexão Cloud são definidos automaticamente pela conta selecionada.</p>
                </div>
                <button type="button" class="text-slate-500 hover:text-slate-700" data-campaign-close>x</button>
            </div>

            <form id="campaignForm" method="POST" action="{{ route('agencia.whatsapp-cloud.campaigns.store') }}" class="grid min-h-0 flex-1 gap-0 lg:grid-cols-3">
                @csrf
                <input type="hidden" name="active_tab" value="campaigns">

                <section class="min-h-0 space-y-4 overflow-y-auto border-r border-slate-200 p-6 lg:col-span-2">
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
                            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</label>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                {{ $selectedCampaignCliente?->nome ?? 'Cliente não identificado' }}
                            </p>
                            <p id="campaignLeadCountHint" class="mt-1 text-xs text-slate-500">Leads elegíveis: -</p>
                        </div>
                    </div>

                    <select id="campaignCliente" name="cliente_id" required class="hidden">
                        @if($selectedCampaignCliente)
                            <option value="{{ $selectedCampaignCliente->id }}" selected>{{ $selectedCampaignCliente->nome }}</option>
                        @else
                            <option value="" selected>Selecione</option>
                        @endif
                    </select>

                    <select id="campaignConexao" name="conexao_id" required class="hidden">
                        @if($selectedCloudConexao)
                            <option
                                value="{{ $selectedCloudConexao->id }}"
                                data-cliente-id="{{ $selectedCloudConexao->cliente_id }}"
                                data-account-id="{{ $selectedCloudConexao->whatsapp_cloud_account_id }}"
                                selected
                            >
                                {{ $selectedCloudConexao->name }}
                            </option>
                        @else
                            <option value="" selected>Selecione</option>
                        @endif
                    </select>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Filtros (opcional)</label>
                        <div id="campaignTagsPicker" class="mt-1">
                            <div class="relative">
                                <button
                                    type="button"
                                    id="campaignFiltersToggle"
                                    class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100"
                                >
                                    Filtros
                                </button>
                                <div id="campaignFiltersDropdown" class="absolute left-0 z-20 mt-2 hidden w-full rounded-xl border border-slate-200 bg-white shadow-xl">
                                    <div class="border-b border-slate-100 p-2">
                                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Opções</p>
                                        <button
                                            type="button"
                                            class="mt-2 inline-flex items-center rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"
                                        >
                                            Tags
                                        </button>
                                    </div>
                                    <div class="space-y-2 p-2">
                                        <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1">
                                            <button
                                                type="button"
                                                id="campaignTagModeInclude"
                                                class="rounded-md bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm"
                                            >é</button>
                                            <button
                                                type="button"
                                                id="campaignTagModeExclude"
                                                class="rounded-md px-3 py-1 text-xs font-semibold text-slate-600"
                                            >não é</button>
                                        </div>
                                        <input
                                            id="campaignTagsSearch"
                                            type="search"
                                            placeholder="Buscar tag"
                                            class="w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                        <div id="campaignTagsOptions" class="max-h-56 space-y-1 overflow-auto">
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
                                        <p class="text-[11px] text-slate-500">Operador atual: <span id="campaignTagModeLabel" class="font-semibold text-slate-700">é</span></p>
                                    </div>
                                </div>
                            </div>
                            <div id="campaignTagsPills" class="mt-2 flex flex-wrap items-center gap-2"></div>
                        </div>
                        <select id="campaignTagIncludeIds" name="tag_include_ids[]" multiple class="hidden">
                            @foreach($campaignTags as $tag)
                                <option
                                    value="{{ $tag->id }}"
                                    data-cliente-id="{{ $tag->cliente_id ?? '' }}"
                                    data-label="{{ $tag->name }}"
                                    @selected(in_array((string) $tag->id, $oldCampaignTagIncludeIds, true))
                                >
                                    {{ $tag->name }}{{ $tag->cliente_id ? '' : ' (global)' }}
                                </option>
                            @endforeach
                        </select>
                        <select id="campaignTagExcludeIds" name="tag_exclude_ids[]" multiple class="hidden">
                            @foreach($campaignTags as $tag)
                                <option
                                    value="{{ $tag->id }}"
                                    data-cliente-id="{{ $tag->cliente_id ?? '' }}"
                                    data-label="{{ $tag->name }}"
                                    @selected(in_array((string) $tag->id, $oldCampaignTagExcludeIds, true))
                                >
                                    {{ $tag->name }}{{ $tag->cliente_id ? '' : ' (global)' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Sem filtros por tag: envia para todos os leads com telefone do cliente escolhido.</p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conexão Cloud</label>
                        <p class="mt-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                            {{ $selectedCloudConexao?->name ?? 'Conexão não identificada' }}
                        </p>
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
                                    data-variables='@json($template->variables ?? [])'
                                    @selected((string) old('whatsapp_cloud_template_id') === (string) $template->id)
                                >
                                    {{ $template->title ?: $template->template_name }} ({{ $template->language_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Variáveis do modelo</p>
                        <p class="mt-1 text-xs text-slate-600">Associe cada variável no padrão <code>var_n</code> com um campo do lead para esta campanha.</p>
                        <div id="campaignVariableBindingsWrap" class="mt-3 hidden rounded-lg border border-slate-200 bg-white p-3">
                            <div id="campaignVariableBindingsList" class="space-y-3"></div>
                        </div>
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

                    <div class="grid gap-4 md:grid-cols-2">
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
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" data-campaign-close>Cancelar</button>
                        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400" @disabled(empty($canCreateCampaign))>Criar campanha</button>
                    </div>
                </section>

                <section class="min-h-0 space-y-4 overflow-y-auto bg-slate-50 p-6 lg:col-span-1">
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
            const oldCampaignTemplateBindings = @json($oldCampaignTemplateBindings);
            const customFields = @json($customFieldsForJs);
            const campaignLeadCounts = @json($campaignLeadCounts);
            const campaignLeadCountUrl = @json(route('agencia.whatsapp-cloud.campaigns.lead-count'));
            const selectedAccountId = @json($accountFilter);

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
            const openAccountModalEmptyState = document.getElementById('openAccountModalEmptyState');
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
            const accountConnectionSection = document.getElementById('accountConnectionSection');
            const accountConexaoName = document.getElementById('accountConexaoName');
            const accountConnectionCliente = document.getElementById('accountConnectionCliente');
            const accountConnectionCredential = document.getElementById('accountConnectionCredential');
            const accountConnectionAssistant = document.getElementById('accountConnectionAssistant');
            const accountConnectionModel = document.getElementById('accountConnectionModel');

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
            const campaignTagsPills = document.getElementById('campaignTagsPills');
            const campaignFiltersToggle = document.getElementById('campaignFiltersToggle');
            const campaignFiltersDropdown = document.getElementById('campaignFiltersDropdown');
            const campaignTagModeInclude = document.getElementById('campaignTagModeInclude');
            const campaignTagModeExclude = document.getElementById('campaignTagModeExclude');
            const campaignTagModeLabel = document.getElementById('campaignTagModeLabel');
            const campaignTagsSearch = document.getElementById('campaignTagsSearch');
            const campaignTagsOptions = document.getElementById('campaignTagsOptions');
            const campaignTagsOptionsEmpty = document.getElementById('campaignTagsOptionsEmpty');
            const campaignTagIncludeIds = document.getElementById('campaignTagIncludeIds');
            const campaignTagExcludeIds = document.getElementById('campaignTagExcludeIds');
            const campaignConexao = document.getElementById('campaignConexao');
            const campaignTemplate = document.getElementById('campaignTemplate');
            const campaignMode = document.getElementById('campaignMode');
            const campaignScheduleWrap = document.getElementById('campaignScheduleWrap');
            const campaignScheduledFor = document.getElementById('campaignScheduledFor');
            const campaignLeadCountHint = document.getElementById('campaignLeadCountHint');
            const campaignVariableBindingsWrap = document.getElementById('campaignVariableBindingsWrap');
            const campaignVariableBindingsList = document.getElementById('campaignVariableBindingsList');
            const campaignPreviewTitle = document.getElementById('campaignPreviewTitle');
            const campaignPreviewMeta = document.getElementById('campaignPreviewMeta');
            const campaignPreviewBody = document.getElementById('campaignPreviewBody');
            const campaignPreviewFooter = document.getElementById('campaignPreviewFooter');
            const campaignPreviewButtons = document.getElementById('campaignPreviewButtons');
            const templateFiltersForm = document.getElementById('templateFiltersForm');
            const templatesAccountFilter = document.getElementById('accountFilter');
            const templatesConexaoFilter = document.getElementById('conexaoFilter');
            const clearTemplateFiltersButton = document.getElementById('clearTemplateFilters');
            const templateRows = Array.from(document.querySelectorAll('[data-template-row]'));
            const templatesNoResultsRow = document.getElementById('templatesNoResultsRow');
            const importMetaTemplatesForm = document.getElementById('importMetaTemplatesForm');
            const importMetaAccountId = document.getElementById('importMetaAccountId');
            const importMetaConexaoId = document.getElementById('importMetaConexaoId');
            let campaignLeadCountRequestId = 0;
            let templateSubmitting = false;
            let campaignTemplateBindingsSeed = (hasErrors && oldActiveTab === 'campaigns' && oldCampaignTemplateBindings && typeof oldCampaignTemplateBindings === 'object')
                ? oldCampaignTemplateBindings
                : {};
            let campaignCurrentTagMode = 'include';

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

            const upsertHiddenInput = (form, name, value) => {
                if (!form) {
                    return;
                }

                let input = form.querySelector(`input[name="${name}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    form.appendChild(input);
                }

                input.value = value || '';
            };

            const syncTemplateActionFormsWithFilters = () => {
                const accountId = templatesAccountFilter?.value || (selectedAccountId ? String(selectedAccountId) : '');
                const conexaoId = templatesConexaoFilter?.value || '';

                document.querySelectorAll('form[data-template-action-form]').forEach((form) => {
                    upsertHiddenInput(form, 'account_id', accountId);
                    upsertHiddenInput(form, 'conexao_id', conexaoId);
                });

                if (importMetaAccountId) {
                    importMetaAccountId.value = accountId;
                }
                if (importMetaConexaoId) {
                    importMetaConexaoId.value = conexaoId;
                }
            };

            const applyTemplateTableFilters = () => {
                const accountId = templatesAccountFilter?.value || '';
                const conexaoId = templatesConexaoFilter?.value || '';
                let visibleCount = 0;

                templateRows.forEach((row) => {
                    const rowAccountId = row.dataset.accountId || '';
                    const rowConexaoId = row.dataset.conexaoId || '';
                    const matchAccount = accountId === '' || rowAccountId === accountId;
                    const matchConexao = conexaoId === '' || rowConexaoId === conexaoId;
                    const visible = matchAccount && matchConexao;
                    row.classList.toggle('hidden', !visible);

                    if (visible) {
                        visibleCount++;
                    }
                });

                if (templatesNoResultsRow) {
                    const shouldShowNoResults = templateRows.length > 0 && visibleCount === 0;
                    templatesNoResultsRow.classList.toggle('hidden', !shouldShowNoResults);
                }

                syncTemplateActionFormsWithFilters();
            };

            templateFiltersForm?.addEventListener('submit', (event) => {
                event.preventDefault();
                applyTemplateTableFilters();
            });

            templatesAccountFilter?.addEventListener('change', applyTemplateTableFilters);
            templatesConexaoFilter?.addEventListener('change', applyTemplateTableFilters);

            clearTemplateFiltersButton?.addEventListener('click', () => {
                if (templatesAccountFilter) {
                    templatesAccountFilter.value = '';
                }
                if (templatesConexaoFilter) {
                    templatesConexaoFilter.value = '';
                }
                applyTemplateTableFilters();
            });

            if (importMetaTemplatesForm) {
                importMetaTemplatesForm.addEventListener('submit', (event) => {
                    syncTemplateActionFormsWithFilters();

                    if (!importMetaAccountId || !importMetaAccountId.value) {
                        event.preventDefault();
                        window.alert('Selecione uma conta para importar os modelos da Meta.');
                    }
                });
            }

            applyTemplateTableFilters();

            const accountConnectionRequiredFields = [
                accountConnectionCliente,
                accountConnectionCredential,
                accountConnectionAssistant,
                accountConnectionModel,
            ];

            const setAccountConnectionMode = (isEditMode) => {
                if (!accountConnectionSection) {
                    return;
                }

                accountConnectionSection.classList.toggle('hidden', isEditMode);

                if (accountConexaoName) {
                    accountConexaoName.disabled = isEditMode;
                    if (isEditMode) {
                        accountConexaoName.value = '';
                    }
                }

                accountConnectionRequiredFields.forEach((field) => {
                    if (!field) {
                        return;
                    }

                    field.disabled = isEditMode;
                    field.required = !isEditMode;
                    if (isEditMode) {
                        field.value = '';
                    }
                });
            };

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
                if (accountConexaoName) {
                    accountConexaoName.value = '';
                }
                accountConnectionRequiredFields.forEach((field) => {
                    if (field) {
                        field.value = '';
                    }
                });
                setAccountConnectionMode(false);
            };

            openAccountModal?.addEventListener('click', () => {
                resetAccountForm();
                openModal(accountModal);
            });
            openAccountModalEmptyState?.addEventListener('click', () => {
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
                    setAccountConnectionMode(true);
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
                templateAccount.value = selectedAccountId ? String(selectedAccountId) : '';
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

            const selectedCampaignTagIds = (mode = 'include') => {
                const select = mode === 'exclude' ? campaignTagExcludeIds : campaignTagIncludeIds;
                if (!select) {
                    return [];
                }

                return Array.from(select.selectedOptions)
                    .map((option) => option.value)
                    .filter((value) => value !== '');
            };

            const findCampaignTagSelectOption = (value, mode = 'include') => {
                const select = mode === 'exclude' ? campaignTagExcludeIds : campaignTagIncludeIds;
                if (!select || !value) {
                    return null;
                }

                return Array.from(select.options).find((option) => option.value === value) || null;
            };

            const setCampaignTagMode = (mode) => {
                campaignCurrentTagMode = mode === 'exclude' ? 'exclude' : 'include';

                const includeActive = campaignCurrentTagMode === 'include';
                campaignTagModeInclude?.classList.toggle('bg-white', includeActive);
                campaignTagModeInclude?.classList.toggle('text-slate-700', includeActive);
                campaignTagModeInclude?.classList.toggle('shadow-sm', includeActive);
                campaignTagModeInclude?.classList.toggle('text-slate-600', !includeActive);

                campaignTagModeExclude?.classList.toggle('bg-white', !includeActive);
                campaignTagModeExclude?.classList.toggle('text-slate-700', !includeActive);
                campaignTagModeExclude?.classList.toggle('shadow-sm', !includeActive);
                campaignTagModeExclude?.classList.toggle('text-slate-600', includeActive);

                if (campaignTagModeLabel) {
                    campaignTagModeLabel.textContent = includeActive ? 'é' : 'não é';
                }

                syncCampaignTagOptionStates();
            };

            const renderCampaignTagPills = () => {
                if (!campaignTagsPills) {
                    return;
                }

                campaignTagsPills.innerHTML = '';
                const includeOptions = campaignTagIncludeIds
                    ? Array.from(campaignTagIncludeIds.selectedOptions).filter((option) => option.value !== '')
                    : [];
                const excludeOptions = campaignTagExcludeIds
                    ? Array.from(campaignTagExcludeIds.selectedOptions).filter((option) => option.value !== '')
                    : [];

                if (includeOptions.length === 0 && excludeOptions.length === 0) {
                    return;
                }

                const appendPill = (option, mode) => {
                    const prefix = mode === 'exclude' ? 'não é' : 'é';
                    const baseClass = mode === 'exclude'
                        ? 'inline-flex items-center gap-2 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700'
                        : 'inline-flex items-center gap-2 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700';
                    const closeClass = mode === 'exclude'
                        ? 'inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-200 text-[10px] font-bold text-rose-700 hover:bg-rose-300'
                        : 'inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-200 text-[10px] font-bold text-emerald-700 hover:bg-emerald-300';

                    const pill = document.createElement('span');
                    pill.className = baseClass;
                    pill.textContent = `${prefix}: ${option.dataset.label || option.textContent || option.value}`;

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.dataset.removeCampaignTag = option.value;
                    removeButton.dataset.mode = mode;
                    removeButton.className = closeClass;
                    removeButton.textContent = 'x';
                    pill.appendChild(removeButton);

                    campaignTagsPills.appendChild(pill);
                };

                includeOptions.forEach((option) => appendPill(option, 'include'));
                excludeOptions.forEach((option) => appendPill(option, 'exclude'));
            };

            const syncCampaignTagOptionStates = () => {
                if (!campaignTagsOptions || !campaignTagIncludeIds || !campaignTagExcludeIds) {
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
                    const includeOption = findCampaignTagSelectOption(value, 'include');
                    const excludeOption = findCampaignTagSelectOption(value, 'exclude');
                    const selectedInInclude = Boolean(includeOption?.selected);
                    const selectedInExclude = Boolean(excludeOption?.selected);
                    const selectedInActive = campaignCurrentTagMode === 'exclude' ? selectedInExclude : selectedInInclude;
                    const allowed = Boolean(includeOption && !includeOption.hidden && !includeOption.disabled);
                    const label = (button.dataset.label || '').toLowerCase();
                    const matchesSearch = searchTerm === '' || label.includes(searchTerm);
                    const visible = allowed && matchesSearch;

                    button.classList.toggle('hidden', !visible);
                    button.classList.toggle('bg-blue-50', campaignCurrentTagMode === 'include' && selectedInActive);
                    button.classList.toggle('bg-rose-50', campaignCurrentTagMode === 'exclude' && selectedInActive);

                    const action = button.querySelector('[data-campaign-tag-action]');
                    if (action) {
                        action.textContent = selectedInActive ? 'Remover' : 'Selecionar';
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
                const currentOption = findCampaignTagSelectOption(value, campaignCurrentTagMode);
                if (!currentOption || currentOption.disabled || currentOption.hidden) {
                    return;
                }

                const nextSelected = !currentOption.selected;
                currentOption.selected = nextSelected;

                if (nextSelected) {
                    const oppositeMode = campaignCurrentTagMode === 'exclude' ? 'include' : 'exclude';
                    const oppositeOption = findCampaignTagSelectOption(value, oppositeMode);
                    if (oppositeOption) {
                        oppositeOption.selected = false;
                    }
                }

                renderCampaignTagPills();
                syncCampaignTagOptionStates();
                updateCampaignLeadCount();
            };

            const updateCampaignTagOptions = () => {
                if (!campaignTagIncludeIds || !campaignTagExcludeIds || !campaignCliente) {
                    return;
                }

                const clienteId = campaignCliente.value || '';
                const updateSelectOptions = (select) => {
                    Array.from(select.options).forEach((option) => {
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
                };

                updateSelectOptions(campaignTagIncludeIds);
                updateSelectOptions(campaignTagExcludeIds);
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

                const includeTagIds = selectedCampaignTagIds('include');
                const excludeTagIds = selectedCampaignTagIds('exclude');
                if (includeTagIds.length === 0 && excludeTagIds.length === 0) {
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
                    includeTagIds.forEach((tagId) => params.append('tag_include_ids[]', tagId));
                    excludeTagIds.forEach((tagId) => params.append('tag_exclude_ids[]', tagId));

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

            const parseCampaignTemplateVariables = (option) => {
                if (!option) {
                    return [];
                }

                let rawVariables = [];
                try {
                    rawVariables = JSON.parse(option.dataset.variables || '[]');
                } catch (error) {
                    rawVariables = [];
                }

                if (!Array.isArray(rawVariables)) {
                    return [];
                }

                const normalized = [];
                rawVariables.forEach((value) => {
                    const variable = (value || '').toString().trim().toLowerCase();
                    if (variable !== '' && !normalized.includes(variable)) {
                        normalized.push(variable);
                    }
                });

                return normalized;
            };

            const getCampaignBindableFields = () => {
                const clienteId = campaignCliente?.value || '';

                return customFields
                    .filter((field) => {
                        const fieldName = (field?.name || '').toString().trim();
                        if (!fieldName) {
                            return false;
                        }

                        const fieldClienteId = field?.cliente_id !== null && field?.cliente_id !== undefined
                            ? String(field.cliente_id)
                            : '';

                        return fieldClienteId === '' || fieldClienteId === clienteId;
                    })
                    .slice()
                    .sort((a, b) => {
                        const labelA = ((a?.label || a?.name || '').toString()).toLowerCase();
                        const labelB = ((b?.label || b?.name || '').toString()).toLowerCase();
                        return labelA.localeCompare(labelB, 'pt-BR');
                    });
            };

            const renderCampaignVariableBindings = () => {
                if (!campaignVariableBindingsWrap || !campaignVariableBindingsList || !campaignTemplate) {
                    return;
                }

                campaignVariableBindingsList.innerHTML = '';

                const selectedOption = campaignTemplate.selectedOptions?.[0];
                const variables = parseCampaignTemplateVariables(selectedOption)
                    .filter((variable) => /^var_\d+$/.test(variable));

                if (!selectedOption || !selectedOption.value || variables.length === 0) {
                    campaignVariableBindingsWrap.classList.add('hidden');
                    return;
                }

                const bindableFields = getCampaignBindableFields();
                variables.forEach((variable) => {
                    const row = document.createElement('div');
                    row.className = 'grid gap-2 md:grid-cols-3';

                    const label = document.createElement('label');
                    label.className = 'text-xs font-semibold uppercase tracking-wide text-slate-500';
                    label.textContent = variable;
                    label.setAttribute('for', `campaignBinding_${variable}`);

                    const select = document.createElement('select');
                    select.id = `campaignBinding_${variable}`;
                    select.name = `template_variable_bindings[${variable}]`;
                    select.required = true;
                    select.className = 'md:col-span-2 rounded-lg border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500';

                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Selecione o campo do lead';
                    select.appendChild(placeholder);

                    bindableFields.forEach((field) => {
                        const option = document.createElement('option');
                        option.value = (field.name || '').toString();
                        const fieldLabel = (field.label || '').toString().trim();
                        option.textContent = fieldLabel !== ''
                            ? `${field.name} - ${fieldLabel}`
                            : `${field.name}`;
                        select.appendChild(option);
                    });

                    const seededValue = (campaignTemplateBindingsSeed?.[variable] || '').toString().trim();
                    if (seededValue !== '') {
                        select.value = seededValue;
                    }

                    row.appendChild(label);
                    row.appendChild(select);
                    campaignVariableBindingsList.appendChild(row);
                });

                campaignVariableBindingsWrap.classList.remove('hidden');
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
                campaignTemplateBindingsSeed = {};
                if (campaignMode) {
                    campaignMode.value = 'immediate';
                }
                if (campaignTagsSearch) {
                    campaignTagsSearch.value = '';
                }
                campaignFiltersDropdown?.classList.add('hidden');
                setCampaignTagMode('include');
                updateCampaignTagOptions();
                updateCampaignLeadCount();
                updateCampaignConexaoOptions();
                updateCampaignTemplateOptions();
                renderCampaignTemplatePreview();
                renderCampaignVariableBindings();
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
                renderCampaignVariableBindings();
            });

            campaignFiltersToggle?.addEventListener('click', () => {
                if (!campaignFiltersDropdown) {
                    return;
                }

                campaignFiltersDropdown.classList.toggle('hidden');
                if (!campaignFiltersDropdown.classList.contains('hidden')) {
                    campaignTagsSearch?.focus();
                }
            });

            campaignTagModeInclude?.addEventListener('click', () => setCampaignTagMode('include'));
            campaignTagModeExclude?.addEventListener('click', () => setCampaignTagMode('exclude'));

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

                if (!removeButton) {
                    return;
                }

                const value = removeButton.dataset.removeCampaignTag || '';
                const mode = removeButton.dataset.mode === 'exclude' ? 'exclude' : 'include';
                const option = findCampaignTagSelectOption(value, mode);
                if (!option) {
                    return;
                }

                option.selected = false;
                renderCampaignTagPills();
                syncCampaignTagOptionStates();
                updateCampaignLeadCount();
            });

            document.addEventListener('click', (event) => {
                if (!campaignFiltersDropdown || !campaignFiltersToggle) {
                    return;
                }

                const target = event.target;
                if (!(target instanceof Node)) {
                    return;
                }

                const clickedInsideDropdown = campaignFiltersDropdown.contains(target);
                const clickedToggle = campaignFiltersToggle.contains(target);
                if (!clickedInsideDropdown && !clickedToggle) {
                    campaignFiltersDropdown.classList.add('hidden');
                }
            });

            campaignConexao?.addEventListener('change', () => {
                updateCampaignTemplateOptions();
                renderCampaignTemplatePreview();
                renderCampaignVariableBindings();
            });

            campaignTemplate?.addEventListener('change', () => {
                renderCampaignTemplatePreview();
                renderCampaignVariableBindings();
            });
            campaignMode?.addEventListener('change', syncCampaignScheduleState);

            const allowedTabs = ['templates', 'campaigns'];
            const initialTab = allowedTabs.includes(oldActiveTab) ? oldActiveTab : 'templates';
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
            renderCampaignVariableBindings();
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
                    campaignTemplateBindingsSeed = (oldCampaignTemplateBindings && typeof oldCampaignTemplateBindings === 'object')
                        ? oldCampaignTemplateBindings
                        : {};
                    updateCampaignTagOptions();
                    updateCampaignLeadCount();
                    updateCampaignConexaoOptions();
                    updateCampaignTemplateOptions();
                    renderCampaignTemplatePreview();
                    renderCampaignVariableBindings();
                    syncCampaignScheduleState();
                    openModal(campaignModal);
                } else {
                    if (oldAccountEditingId) {
                        accountForm.action = `{{ url('/agencia/whatsapp-api-cloud/contas') }}/${oldAccountEditingId}`;
                        accountFormMethod.value = 'PATCH';
                        accountModalTitle.textContent = 'Editar conta cloud';
                        accountToken.required = false;
                        accountTokenHint.textContent = 'Opcional em edição. Preencha apenas se desejar substituir o token atual.';
                        setAccountConnectionMode(true);
                    } else {
                        accountToken.required = true;
                        accountTokenHint.textContent = 'Obrigatório para criar. Em edição, preencha apenas se quiser trocar.';
                        setAccountConnectionMode(false);
                    }
                    openModal(accountModal);
                }
            }
        })();
    </script>
@endsection
