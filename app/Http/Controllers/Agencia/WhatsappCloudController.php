<?php

namespace App\Http\Controllers\Agencia;

use App\Jobs\DispatchWhatsappCloudCampaignJob;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\Tag;
use App\Models\User;
use App\Models\WhatsappCloudAccount;
use App\Models\WhatsappCloudCampaign;
use App\Models\WhatsappCloudCustomField;
use App\Models\WhatsappCloudTemplate;
use App\Services\ScheduledMessageService;
use App\Services\WhatsappCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WhatsappCloudController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $accountFilter = $request->integer('account_id') ?: null;
        $conexaoFilter = $request->integer('conexao_id') ?: null;

        $accounts = WhatsappCloudAccount::query()
            ->where('user_id', $user->id)
            ->with('conexoes:id,name,whatsapp_cloud_account_id')
            ->withCount(['templates', 'conexoes'])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $conexoes = $this->ownedCloudConexoesQuery($user->id)
            ->with('cliente')
            ->orderBy('name')
            ->get();

        $templatesQuery = WhatsappCloudTemplate::query()
            ->where('user_id', $user->id)
            ->with(['account', 'conexao.cliente'])
            ->latest();

        if ($accountFilter) {
            $templatesQuery->where('whatsapp_cloud_account_id', $accountFilter);
        }

        if ($conexaoFilter) {
            $templatesQuery->where('conexao_id', $conexaoFilter);
        }

        $templates = $templatesQuery->get();

        $customFields = WhatsappCloudCustomField::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'label', 'sample_value']);

        $campaignClientes = Cliente::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $campaignTags = Tag::query()
            ->where('user_id', $user->id)
            ->orderByRaw('CASE WHEN cliente_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get(['id', 'name', 'cliente_id']);

        $campaignConexoes = $this->ownedCloudConexoesQuery((int) $user->id)
            ->whereNull('deleted_at')
            ->whereNotNull('whatsapp_cloud_account_id')
            ->with('cliente:id,nome')
            ->orderBy('name')
            ->get(['id', 'name', 'cliente_id', 'whatsapp_cloud_account_id']);

        $campaignTemplates = WhatsappCloudTemplate::query()
            ->where('user_id', $user->id)
            ->whereRaw('UPPER(status) IN (?, ?)', ['APPROVED', 'ACTIVE'])
            ->orderBy('title')
            ->orderBy('template_name')
            ->get([
                'id',
                'conexao_id',
                'whatsapp_cloud_account_id',
                'title',
                'template_name',
                'language_code',
                'status',
                'body_text',
                'footer_text',
                'buttons',
                'variables',
            ]);

        $campaignLeadCounts = ClienteLead::query()
            ->whereIn('cliente_id', $campaignClientes->pluck('id')->all())
            ->selectRaw('cliente_id, COUNT(*) as total')
            ->groupBy('cliente_id')
            ->pluck('total', 'cliente_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $campaigns = WhatsappCloudCampaign::query()
            ->where('user_id', $user->id)
            ->with([
                'cliente:id,nome',
                'conexao:id,name,cliente_id',
                'template:id,title,template_name,language_code',
            ])
            ->latest()
            ->limit(80)
            ->get();

        $this->ensureUserWebhookCredentials($user);

        $webhookUrl = route('api.whatsapp-cloud.webhook', [
            'webhookKey' => (string) $user->whatsapp_cloud_webhook_key,
        ]);
        $accountsWithAppSecret = $accounts->filter(function (WhatsappCloudAccount $account): bool {
            return trim((string) ($account->app_secret ?? '')) !== '';
        })->count();

        return view('agencia.whatsapp-api-cloud.index', [
            'accounts' => $accounts,
            'conexoes' => $conexoes,
            'templates' => $templates,
            'customFields' => $customFields,
            'accountFilter' => $accountFilter,
            'conexaoFilter' => $conexaoFilter,
            'userWebhookVerifyToken' => (string) $user->whatsapp_cloud_webhook_verify_token,
            'webhookUrl' => $webhookUrl,
            'accountsWithAppSecret' => $accountsWithAppSecret,
            'campaignClientes' => $campaignClientes,
            'campaignTags' => $campaignTags,
            'campaignConexoes' => $campaignConexoes,
            'campaignTemplates' => $campaignTemplates,
            'campaignLeadCounts' => $campaignLeadCounts,
            'campaigns' => $campaigns,
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'active_tab' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:120'],
            'phone_number_id' => [
                'required',
                'string',
                'regex:/^\d+$/',
                'max:50',
                Rule::unique('whatsapp_cloud_accounts', 'phone_number_id')->where('user_id', $user->id),
            ],
            'business_account_id' => ['nullable', 'string', 'regex:/^\d+$/', 'max:50'],
            'app_id' => ['nullable', 'string', 'regex:/^\d+$/', 'max:80'],
            'app_secret' => ['nullable', 'string', 'max:500'],
            'access_token' => ['required', 'string', 'max:4000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $account = new WhatsappCloudAccount();
        $account->user_id = $user->id;
        $account->name = trim((string) $data['name']);
        $account->phone_number_id = trim((string) $data['phone_number_id']);
        $account->business_account_id = isset($data['business_account_id']) && trim((string) $data['business_account_id']) !== ''
            ? trim((string) $data['business_account_id'])
            : null;
        $account->app_id = isset($data['app_id']) && trim((string) $data['app_id']) !== ''
            ? trim((string) $data['app_id'])
            : null;
        if (isset($data['app_secret']) && trim((string) $data['app_secret']) !== '') {
            $account->app_secret = $data['app_secret'];
        }
        $account->access_token = $data['access_token'];
        $account->is_default = $request->boolean('is_default');
        $account->save();

        if ($account->is_default) {
            $this->clearOtherDefaultAccounts($user->id, $account->id);
        }

        return redirect()
            ->route('agencia.whatsapp-cloud.index', [
                'account_id' => $account->id,
            ])
            ->with('success', 'Conta WhatsApp Cloud criada com sucesso.');
    }

    public function updateAccount(Request $request, WhatsappCloudAccount $account): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAccountOwnership($account, $user->id);

        $data = $request->validate([
            'active_tab' => ['nullable', 'string'],
            'account_editing_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'phone_number_id' => [
                'required',
                'string',
                'regex:/^\d+$/',
                'max:50',
                Rule::unique('whatsapp_cloud_accounts', 'phone_number_id')
                    ->where('user_id', $user->id)
                    ->ignore($account->id),
            ],
            'business_account_id' => ['nullable', 'string', 'regex:/^\d+$/', 'max:50'],
            'app_id' => ['nullable', 'string', 'regex:/^\d+$/', 'max:80'],
            'app_secret' => ['nullable', 'string', 'max:500'],
            'access_token' => ['nullable', 'string', 'max:4000'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $account->name = trim((string) $data['name']);
        $account->phone_number_id = trim((string) $data['phone_number_id']);
        $account->business_account_id = isset($data['business_account_id']) && trim((string) $data['business_account_id']) !== ''
            ? trim((string) $data['business_account_id'])
            : null;
        $account->app_id = isset($data['app_id']) && trim((string) $data['app_id']) !== ''
            ? trim((string) $data['app_id'])
            : null;

        if (isset($data['app_secret']) && trim((string) $data['app_secret']) !== '') {
            $account->app_secret = $data['app_secret'];
        }

        if (isset($data['access_token']) && trim((string) $data['access_token']) !== '') {
            $account->access_token = $data['access_token'];
        }

        $account->is_default = $request->boolean('is_default');
        $account->save();

        if ($account->is_default) {
            $this->clearOtherDefaultAccounts($user->id, $account->id);
        }

        return redirect()
            ->route('agencia.whatsapp-cloud.index', [
                'account_id' => $account->id,
            ])
            ->with('success', 'Conta WhatsApp Cloud atualizada com sucesso.');
    }

    public function rotateWebhookKey(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureUserWebhookCredentials($user, true);

        return redirect()
            ->route('agencia.whatsapp-cloud.index')
            ->with('success', 'Nova chave de webhook gerada com sucesso.');
    }

    private function ensureUserWebhookCredentials(User $user, bool $forceRotateKey = false): void
    {
        $needsSave = false;

        if ($forceRotateKey || trim((string) ($user->whatsapp_cloud_webhook_key ?? '')) === '') {
            $user->whatsapp_cloud_webhook_key = $this->generateUniqueUserToken('whatsapp_cloud_webhook_key', 'wcu_');
            $needsSave = true;
        }

        if (trim((string) ($user->whatsapp_cloud_webhook_verify_token ?? '')) === '') {
            $user->whatsapp_cloud_webhook_verify_token = $this->generateUniqueUserToken('whatsapp_cloud_webhook_verify_token', 'wvu_');
            $needsSave = true;
        }

        if ($needsSave) {
            $user->save();
        }
    }

    public function destroyAccount(Request $request, WhatsappCloudAccount $account): RedirectResponse
    {
        $this->ensureAccountOwnership($account, $request->user()->id);

        $account->delete();

        return redirect()
            ->route('agencia.whatsapp-cloud.index')
            ->with('success', 'Conta WhatsApp Cloud removida com sucesso.');
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validateTemplatePayload($request, false);

        $account = $this->findOwnedAccount((int) $data['whatsapp_cloud_account_id'], $user->id);
        $conexao = $this->resolveOwnedTemplateConexao($data['conexao_id'] ?? null, $user->id, $account->id);
        $buttons = $this->normalizeButtons((array) ($data['buttons'] ?? []));

        $this->validateButtonRows($buttons);
        $this->validatePlaceholderSyntax((string) $data['body_text'], $data['footer_text'] ?? null, $buttons);

        $variables = $this->extractTemplateVariables((string) $data['body_text'], $data['footer_text'] ?? null, $buttons);
        $this->ensureVariablesExistForUser($variables, $user->id);

        $variableExamples = $this->resolveVariableExamples($user->id, $variables, (array) ($data['variable_examples'] ?? []));

        $templateName = $this->generateUniqueTemplateName(
            (string) $data['title'],
            $account->id,
            (string) $data['language_code'],
            null
        );

        $metaPayload = $this->buildMetaTemplatePayload([
            'name' => $templateName,
            'language_code' => (string) $data['language_code'],
            'category' => (string) $data['category'],
            'body_text' => (string) $data['body_text'],
            'footer_text' => $data['footer_text'] ?? null,
            'buttons' => $buttons,
            'variable_examples' => $variableExamples,
        ]);

        $syncResult = $this->syncTemplateWithMeta($account, $metaPayload, null);
        if (!empty($syncResult['error'])) {
            throw ValidationException::withMessages([
                'meta_sync' => [$this->resolveMetaSyncError($syncResult)],
            ]);
        }

        WhatsappCloudTemplate::create([
            'user_id' => $user->id,
            'whatsapp_cloud_account_id' => $account->id,
            'conexao_id' => $conexao?->id,
            'title' => trim((string) $data['title']),
            'template_name' => $templateName,
            'language_code' => trim((string) $data['language_code']),
            'category' => trim((string) $data['category']),
            'variables' => $variables,
            'body_text' => trim((string) $data['body_text']),
            'footer_text' => $this->nullableTrim($data['footer_text'] ?? null),
            'buttons' => !empty($buttons) ? $buttons : null,
            'variable_examples' => !empty($variableExamples) ? $variableExamples : null,
            'status' => $this->resolveTemplateStatus($syncResult, 'PENDING'),
            'meta_template_id' => $this->resolveMetaTemplateId($syncResult),
            'last_synced_at' => now(),
            'last_sync_error' => null,
        ]);

        return redirect()
            ->route('agencia.whatsapp-cloud.index', ['account_id' => $account->id])
            ->with('success', 'Modelo criado e sincronizado com a Meta com sucesso.');
    }

    public function updateTemplate(Request $request, WhatsappCloudTemplate $template): RedirectResponse
    {
        $user = $request->user();
        $this->ensureTemplateOwnership($template, $user->id);

        $data = $this->validateTemplatePayload($request, true);
        $account = $this->findOwnedAccount((int) $data['whatsapp_cloud_account_id'], $user->id);
        $conexao = $this->resolveOwnedTemplateConexao($data['conexao_id'] ?? null, $user->id, $account->id);
        $buttons = $this->normalizeButtons((array) ($data['buttons'] ?? []));

        $this->validateButtonRows($buttons);
        $this->validatePlaceholderSyntax((string) $data['body_text'], $data['footer_text'] ?? null, $buttons);

        $variables = $this->extractTemplateVariables((string) $data['body_text'], $data['footer_text'] ?? null, $buttons);
        $this->ensureVariablesExistForUser($variables, $user->id);

        $variableExamples = $this->resolveVariableExamples($user->id, $variables, (array) ($data['variable_examples'] ?? []));

        $currentAccountId = (int) ($template->whatsapp_cloud_account_id ?? 0);
        $currentLanguageCode = trim((string) ($template->language_code ?? ''));
        $nextLanguageCode = trim((string) $data['language_code']);

        // Evita recriar template desnecessariamente ao editar título/corpo.
        // A Meta não permite renomear template existente; nesse caso mantemos o nome interno atual.
        $keepCurrentTemplateName = $currentAccountId === (int) $account->id
            && $currentLanguageCode === $nextLanguageCode
            && trim((string) ($template->template_name ?? '')) !== '';

        $templateName = $keepCurrentTemplateName
            ? trim((string) $template->template_name)
            : $this->generateUniqueTemplateName(
                (string) $data['title'],
                $account->id,
                $nextLanguageCode,
                $template->id
            );

        $metaPayload = $this->buildMetaTemplatePayload([
            'name' => $templateName,
            'language_code' => (string) $data['language_code'],
            'category' => (string) $data['category'],
            'body_text' => (string) $data['body_text'],
            'footer_text' => $data['footer_text'] ?? null,
            'buttons' => $buttons,
            'variable_examples' => $variableExamples,
        ]);

        $syncResult = $this->syncTemplateWithMeta($account, $metaPayload, $template);
        if (!empty($syncResult['error'])) {
            throw ValidationException::withMessages([
                'meta_sync' => [$this->resolveMetaSyncError($syncResult)],
            ]);
        }

        $template->update([
            'whatsapp_cloud_account_id' => $account->id,
            'conexao_id' => $conexao?->id,
            'title' => trim((string) $data['title']),
            'template_name' => $templateName,
            'language_code' => trim((string) $data['language_code']),
            'category' => trim((string) $data['category']),
            'variables' => $variables,
            'body_text' => trim((string) $data['body_text']),
            'footer_text' => $this->nullableTrim($data['footer_text'] ?? null),
            'buttons' => !empty($buttons) ? $buttons : null,
            'variable_examples' => !empty($variableExamples) ? $variableExamples : null,
            'status' => $this->resolveTemplateStatus($syncResult, $template->status ?: 'PENDING'),
            'meta_template_id' => $this->resolveMetaTemplateId($syncResult) ?: $template->meta_template_id,
            'last_synced_at' => now(),
            'last_sync_error' => null,
        ]);

        return redirect()
            ->route('agencia.whatsapp-cloud.index', ['account_id' => $account->id])
            ->with('success', 'Modelo atualizado e sincronizado com a Meta com sucesso.');
    }

    public function destroyTemplate(Request $request, WhatsappCloudTemplate $template): RedirectResponse
    {
        $this->ensureTemplateOwnership($template, $request->user()->id);

        if ($template->account?->business_account_id && $template->account?->access_token && $template->template_name) {
            $service = app(WhatsappCloudApiService::class);
            $service->deleteMessageTemplateByName(
                (string) $template->account->business_account_id,
                (string) $template->template_name,
                ['access_token' => (string) $template->account->access_token]
            );
        }

        $template->delete();

        return redirect()
            ->route('agencia.whatsapp-cloud.index')
            ->with('success', 'Modelo de mensagem removido com sucesso.');
    }

    public function refreshTemplateStatus(Request $request, WhatsappCloudTemplate $template): RedirectResponse
    {
        $this->ensureTemplateOwnership($template, $request->user()->id);
        $refreshResult = $this->refreshTemplateStatusSnapshot($template);
        if (!$refreshResult['ok']) {
            return $this->redirectToTemplateList($request, $template)
                ->with('error', (string) $refreshResult['message']);
        }

        return $this->redirectToTemplateList($request, $template)
            ->with('success', 'Status do modelo atualizado com sucesso.');
    }

    public function refreshTemplateStatusBulk(Request $request): RedirectResponse
    {
        $userId = (int) $request->user()->id;
        $data = $request->validate([
            'active_tab' => ['nullable', 'string'],
            'account_id' => ['nullable', 'integer'],
            'conexao_id' => ['nullable', 'integer'],
        ]);

        $accountId = isset($data['account_id']) ? (int) $data['account_id'] : null;
        $conexaoId = isset($data['conexao_id']) ? (int) $data['conexao_id'] : null;

        $templatesQuery = WhatsappCloudTemplate::query()
            ->where('user_id', $userId)
            ->with('account')
            ->orderBy('id');

        if ($accountId && $accountId > 0) {
            $templatesQuery->where('whatsapp_cloud_account_id', $accountId);
        }

        if ($conexaoId && $conexaoId > 0) {
            $templatesQuery->where('conexao_id', $conexaoId);
        }

        $templates = $templatesQuery->get();
        if ($templates->isEmpty()) {
            return redirect()
                ->route('agencia.whatsapp-cloud.index', array_filter([
                    'tab' => 'templates',
                    'account_id' => $accountId,
                    'conexao_id' => $conexaoId,
                ]))
                ->with('error', 'Nenhum template encontrado para os filtros informados.');
        }

        $successCount = 0;
        $errorCount = 0;
        $errorExamples = [];

        foreach ($templates as $template) {
            $result = $this->refreshTemplateStatusSnapshot($template);
            if ($result['ok']) {
                $successCount++;
                continue;
            }

            $errorCount++;
            if (count($errorExamples) < 3) {
                $errorExamples[] = (string) $template->template_name . ': ' . (string) $result['message'];
            }
        }

        $message = "Atualização em lote finalizada. Sucesso: {$successCount}. Falhas: {$errorCount}.";
        if (!empty($errorExamples)) {
            $message .= ' Exemplos: ' . implode(' | ', $errorExamples);
        }

        return redirect()
            ->route('agencia.whatsapp-cloud.index', array_filter([
                'tab' => 'templates',
                'account_id' => $accountId,
                'conexao_id' => $conexaoId,
            ]))
            ->with($errorCount > 0 ? 'error' : 'success', $message);
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $user = $request->user();
        $userId = (int) $user->id;

        $data = $request->validate([
            'active_tab' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:160'],
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
            'tag_ids' => ['nullable', 'array', 'max:200'],
            'tag_ids.*' => ['integer'],
            'conexao_id' => ['required', 'integer'],
            'whatsapp_cloud_template_id' => ['required', 'integer'],
            'assistant_context_instructions' => ['nullable', 'string', 'max:4000'],
            'mode' => ['required', 'in:immediate,scheduled'],
            'scheduled_for' => ['nullable', 'string', 'max:50'],
            'interval_seconds' => ['nullable', 'integer', 'min:0', 'max:120'],
        ]);

        $clienteId = (int) $data['cliente_id'];
        $conexaoId = (int) $data['conexao_id'];
        $templateId = (int) $data['whatsapp_cloud_template_id'];
        $selectedTagIds = $this->resolveCampaignTagIds((array) ($data['tag_ids'] ?? []), $userId, $clienteId);

        $conexao = $this->ownedCloudConexoesQuery($userId)
            ->whereNull('deleted_at')
            ->whereNotNull('whatsapp_cloud_account_id')
            ->findOrFail($conexaoId);

        if ((int) $conexao->cliente_id !== $clienteId) {
            throw ValidationException::withMessages([
                'conexao_id' => ['A conexão selecionada não pertence ao cliente informado.'],
            ]);
        }

        $template = WhatsappCloudTemplate::query()
            ->where('user_id', $userId)
            ->findOrFail($templateId);

        $templateStatus = Str::upper(trim((string) ($template->status ?? '')));
        if (!in_array($templateStatus, ['APPROVED', 'ACTIVE'], true)) {
            throw ValidationException::withMessages([
                'whatsapp_cloud_template_id' => ['Somente modelos aprovados/ativos podem ser usados em campanha.'],
            ]);
        }

        $accountId = (int) ($conexao->whatsapp_cloud_account_id ?? 0);
        if ($accountId <= 0) {
            throw ValidationException::withMessages([
                'conexao_id' => ['A conexão selecionada não possui conta Cloud vinculada.'],
            ]);
        }

        if ((int) $template->whatsapp_cloud_account_id !== $accountId) {
            throw ValidationException::withMessages([
                'whatsapp_cloud_template_id' => ['O modelo selecionado não pertence à conta Cloud da conexão informada.'],
            ]);
        }

        if ($template->conexao_id !== null && (int) $template->conexao_id !== $conexaoId) {
            throw ValidationException::withMessages([
                'whatsapp_cloud_template_id' => ['O modelo selecionado está restrito a outra conexão.'],
            ]);
        }

        $mode = (string) $data['mode'];
        $intervalSeconds = max(0, (int) ($data['interval_seconds'] ?? 2));
        $assistantContextInstructions = trim((string) ($data['assistant_context_instructions'] ?? ''));
        if ($assistantContextInstructions === '') {
            $assistantContextInstructions = null;
        }

        $scheduledForUtc = null;
        if ($mode === 'scheduled') {
            $scheduledForRaw = trim((string) ($data['scheduled_for'] ?? ''));
            if ($scheduledForRaw === '') {
                throw ValidationException::withMessages([
                    'scheduled_for' => ['Informe a data/hora para o envio programado.'],
                ]);
            }

            /** @var ScheduledMessageService $scheduledMessageService */
            $scheduledMessageService = app(ScheduledMessageService::class);
            $timezone = $scheduledMessageService->resolveTimezoneForUser($user);
            $scheduledForUtc = $scheduledMessageService->parseScheduledForToUtc($scheduledForRaw, $timezone);

            if (!$scheduledForUtc) {
                throw ValidationException::withMessages([
                    'scheduled_for' => ['Data/hora inválida para agendamento.'],
                ]);
            }

            if ($scheduledForUtc->lte(Carbon::now('UTC'))) {
                throw ValidationException::withMessages([
                    'scheduled_for' => ['A data/hora programada deve estar no futuro.'],
                ]);
            }
        }

        $leadsQuery = $this->buildCampaignLeadsQuery($clienteId, $selectedTagIds);

        $totalLeads = (clone $leadsQuery)->count();
        if ($totalLeads <= 0) {
            throw ValidationException::withMessages([
                'cliente_id' => ['Nenhum lead com telefone válido encontrado para este cliente.'],
            ]);
        }

        if ($totalLeads > 10000) {
            throw ValidationException::withMessages([
                'tag_ids' => ['A seleção retornou ' . $totalLeads . ' leads. O limite por campanha é 10000. Refine por tags.'],
            ]);
        }

        $campaignName = trim((string) ($data['name'] ?? ''));
        if ($campaignName === '') {
            $campaignName = 'Campanha ' . now()->format('d/m H:i');
        }

        $nowUtc = Carbon::now('UTC');
        $campaign = null;

        DB::transaction(function () use (
            &$campaign,
            $userId,
            $campaignName,
            $clienteId,
            $conexaoId,
            $accountId,
            $templateId,
            $mode,
            $scheduledForUtc,
            $totalLeads,
            $intervalSeconds,
            $assistantContextInstructions,
            $leadsQuery,
            $selectedTagIds,
            $nowUtc
        ): void {
            $campaign = WhatsappCloudCampaign::create([
                'user_id' => $userId,
                'created_by_user_id' => $userId,
                'cliente_id' => $clienteId,
                'conexao_id' => $conexaoId,
                'whatsapp_cloud_account_id' => $accountId,
                'whatsapp_cloud_template_id' => $templateId,
                'name' => $campaignName,
                'mode' => $mode,
                'status' => $mode === 'scheduled' ? 'scheduled' : 'draft',
                'scheduled_for' => $scheduledForUtc,
                'total_leads' => $totalLeads,
                'settings' => [
                    'interval_seconds' => $intervalSeconds,
                    'assistant_context_instructions' => $assistantContextInstructions,
                ],
                'filter_payload' => [
                    'source' => empty($selectedTagIds) ? 'cliente_all_leads' : 'cliente_tags',
                    'tag_ids' => $selectedTagIds,
                ],
            ]);

            $leadsQuery->orderBy('id')->chunkById(500, function ($leads) use ($campaign, $nowUtc): void {
                $rows = [];

                foreach ($leads as $lead) {
                    $leadId = (int) ($lead->id ?? 0);
                    if ($leadId <= 0) {
                        continue;
                    }

                    $phone = preg_replace('/\D/', '', (string) ($lead->phone ?? ''));
                    $rows[] = [
                        'whatsapp_cloud_campaign_id' => (int) $campaign->id,
                        'cliente_lead_id' => $leadId,
                        'phone' => is_string($phone) && $phone !== '' ? $phone : null,
                        'status' => 'pending',
                        'attempts' => 0,
                        'idempotency_key' => 'wcc_item_' . hash('sha256', "{$campaign->id}:{$leadId}"),
                        'created_at' => $nowUtc,
                        'updated_at' => $nowUtc,
                    ];
                }

                if (!empty($rows)) {
                    DB::table('whatsapp_cloud_campaign_items')->insert($rows);
                }
            });
        });

        if (!$campaign instanceof WhatsappCloudCampaign) {
            return redirect()
                ->route('agencia.whatsapp-cloud.index', ['tab' => 'campaigns'])
                ->with('error', 'Não foi possível criar a campanha.');
        }

        $dispatch = DispatchWhatsappCloudCampaignJob::dispatch((int) $campaign->id)
            ->onQueue('processarconversa');

        if ($scheduledForUtc) {
            $dispatch->delay($scheduledForUtc);
        }

        $successMessage = $scheduledForUtc
            ? "Campanha criada e programada com {$totalLeads} lead(s)."
            : "Campanha criada com {$totalLeads} lead(s) e envio iniciado.";

        return redirect()
            ->route('agencia.whatsapp-cloud.index', ['tab' => 'campaigns'])
            ->with('success', $successMessage);
    }

    public function campaignLeadCount(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $data = $request->validate([
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($query) => $query->where('user_id', $userId)->whereNull('deleted_at')),
            ],
            'tag_ids' => ['nullable', 'array', 'max:200'],
            'tag_ids.*' => ['integer'],
        ]);

        $clienteId = (int) $data['cliente_id'];
        $tagIds = $this->resolveCampaignTagIds((array) ($data['tag_ids'] ?? []), $userId, $clienteId);
        $count = $this->buildCampaignLeadsQuery($clienteId, $tagIds)->count();

        return response()->json([
            'count' => (int) $count,
            'over_limit' => $count > 10000,
        ]);
    }

    public function cancelCampaign(Request $request, WhatsappCloudCampaign $campaign): RedirectResponse
    {
        $this->ensureCampaignOwnership($campaign, (int) $request->user()->id);

        if (in_array($campaign->status, ['completed', 'failed', 'canceled'], true)) {
            return redirect()
                ->route('agencia.whatsapp-cloud.index', ['tab' => 'campaigns'])
                ->with('error', 'Esta campanha já foi finalizada e não pode ser cancelada.');
        }

        $nowUtc = Carbon::now('UTC');
        DB::transaction(function () use ($campaign, $nowUtc): void {
            $campaign->forceFill([
                'status' => 'canceled',
                'canceled_at' => $nowUtc,
                'finished_at' => $campaign->finished_at ?: $nowUtc,
            ])->save();

            DB::table('whatsapp_cloud_campaign_items')
                ->where('whatsapp_cloud_campaign_id', (int) $campaign->id)
                ->whereIn('status', ['pending', 'queued'])
                ->update([
                    'status' => 'canceled',
                    'error_message' => 'Item cancelado junto com a campanha.',
                    'skipped_at' => $nowUtc,
                    'updated_at' => $nowUtc,
                ]);
        });

        return redirect()
            ->route('agencia.whatsapp-cloud.index', ['tab' => 'campaigns'])
            ->with('success', 'Campanha cancelada com sucesso.');
    }

    private function validateTemplatePayload(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'active_tab' => ['nullable', 'string'],
            'template_editing_id' => [$isUpdate ? 'required' : 'nullable', 'integer'],
            'whatsapp_cloud_account_id' => ['required', 'integer'],
            'conexao_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:120'],
            'language_code' => ['required', 'string', 'max:20'],
            'category' => ['required', 'in:UTILITY,MARKETING'],
            'body_text' => ['required', 'string', 'max:4000'],
            'footer_text' => ['nullable', 'string', 'max:4000'],
            'buttons' => ['nullable', 'array', 'max:10'],
            'buttons.*.type' => ['nullable', 'in:QUICK_REPLY,URL'],
            'buttons.*.text' => ['nullable', 'string', 'max:100'],
            'buttons.*.url' => ['nullable', 'string', 'max:2000'],
            'variable_examples' => ['nullable', 'array'],
            'variable_examples.*' => ['nullable', 'string', 'max:500'],
        ], [], [
            'title' => 'nome do modelo',
            'body_text' => 'mensagem',
        ]);
    }

    private function validateButtonRows(array $buttons): void
    {
        foreach ($buttons as $index => $button) {
            $position = $index + 1;

            if ($button['type'] === 'QUICK_REPLY' && ($button['text'] ?? '') === '') {
                throw ValidationException::withMessages([
                    "buttons.{$index}.text" => ["Informe o texto do botão de resposta rápida na linha {$position}."],
                ]);
            }

            if ($button['type'] === 'URL') {
                if (($button['text'] ?? '') === '') {
                    throw ValidationException::withMessages([
                        "buttons.{$index}.text" => ["Informe o texto do botão URL na linha {$position}."],
                    ]);
                }

                if (($button['url'] ?? '') === '') {
                    throw ValidationException::withMessages([
                        "buttons.{$index}.url" => ["Informe a URL do botão na linha {$position}."],
                    ]);
                }

                if (!$this->isTemplateUrlValid((string) $button['url'])) {
                    throw ValidationException::withMessages([
                        "buttons.{$index}.url" => ["A URL do botão na linha {$position} é inválida."],
                    ]);
                }
            }
        }
    }

    private function normalizeButtons(array $rawButtons): array
    {
        $normalized = [];

        foreach ($rawButtons as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = Str::upper(trim((string) ($row['type'] ?? '')));
            $text = trim((string) ($row['text'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));

            if ($type === '' && $text === '' && $url === '') {
                continue;
            }

            if ($type === '') {
                $type = $url !== '' ? 'URL' : 'QUICK_REPLY';
            }

            $normalized[] = [
                'type' => $type,
                'text' => $text,
                'url' => $type === 'URL' ? $url : null,
            ];
        }

        return $normalized;
    }

    private function validatePlaceholderSyntax(string $bodyText, ?string $footerText, array $buttons): void
    {
        $texts = [$bodyText, $footerText ?? ''];
        foreach ($buttons as $button) {
            $texts[] = (string) ($button['text'] ?? '');
            $texts[] = (string) ($button['url'] ?? '');
        }

        foreach ($texts as $text) {
            if (!is_string($text) || $text === '') {
                continue;
            }

            preg_match_all('/\{([^}]+)\}/', $text, $allMatches);
            $allVariables = $allMatches[1] ?? [];
            foreach ($allVariables as $candidate) {
                if (!preg_match('/^[a-z0-9_]+$/', $candidate)) {
                    throw ValidationException::withMessages([
                        'body_text' => ["Variável inválida {{$candidate}}. Use apenas letras minúsculas, números e underscore."],
                    ]);
                }
            }
        }
    }

    private function extractTemplateVariables(string $bodyText, ?string $footerText, array $buttons): array
    {
        $variables = [];

        $push = function (string $text) use (&$variables): void {
            if ($text === '') {
                return;
            }

            preg_match_all('/\{([a-z0-9_]+)\}/', $text, $matches);
            foreach (($matches[1] ?? []) as $name) {
                if (!in_array($name, $variables, true)) {
                    $variables[] = $name;
                }
            }
        };

        $push($bodyText);
        $push((string) ($footerText ?? ''));

        foreach ($buttons as $button) {
            $push((string) ($button['text'] ?? ''));
            $push((string) ($button['url'] ?? ''));
        }

        return $variables;
    }

    private function ensureVariablesExistForUser(array $variables, int $userId): void
    {
        if (empty($variables)) {
            return;
        }

        $existingNames = WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->whereIn('name', $variables)
            ->pluck('name')
            ->all();

        $missing = array_values(array_diff($variables, $existingNames));
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'body_text' => ['Campos personalizados não encontrados: ' . implode(', ', $missing) . '.'],
            ]);
        }
    }

    private function resolveVariableExamples(int $userId, array $variables, array $rawExamples): array
    {
        if (empty($variables)) {
            return [];
        }

        $defaults = WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->whereIn('name', $variables)
            ->pluck('sample_value', 'name')
            ->all();

        $resolved = [];

        foreach ($variables as $variable) {
            $manual = isset($rawExamples[$variable]) ? trim((string) $rawExamples[$variable]) : '';
            $fallback = isset($defaults[$variable]) ? trim((string) $defaults[$variable]) : '';
            $value = $manual !== '' ? $manual : $fallback;

            if ($value === '') {
                throw ValidationException::withMessages([
                    "variable_examples.{$variable}" => ["Informe um exemplo para a variável {{$variable}}."],
                ]);
            }

            $resolved[$variable] = $value;
        }

        return $resolved;
    }

    private function generateUniqueTemplateName(string $title, int $accountId, string $languageCode, ?int $ignoreTemplateId): string
    {
        $base = $this->normalizeTemplateName($title);

        $query = WhatsappCloudTemplate::query()
            ->where('whatsapp_cloud_account_id', $accountId)
            ->where('language_code', trim($languageCode));

        if ($ignoreTemplateId) {
            $query->where('id', '!=', $ignoreTemplateId);
        }

        $existing = $query->pluck('template_name')->all();

        if (!in_array($base, $existing, true)) {
            return $base;
        }

        $index = 2;
        do {
            $suffix = (string) $index;
            $trimmedBase = Str::limit($base, max(1, 255 - strlen($suffix)), '');
            $candidate = $trimmedBase . $suffix;
            $index++;
        } while (in_array($candidate, $existing, true));

        return $candidate;
    }

    private function normalizeTemplateName(string $title): string
    {
        $value = Str::ascii($title);
        $value = Str::lower($value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? '';
        $value = trim($value, '_');

        if ($value === '') {
            $value = 'modelo_template';
        }

        if (preg_match('/^\d/', $value)) {
            $value = 'template_' . $value;
        }

        return Str::limit($value, 255, '');
    }

    private function buildMetaTemplatePayload(array $data): array
    {
        $globalIndexMap = [];
        $components = [];

        [$bodyText, $bodyVariables] = $this->transformTextWithMetaIndexes((string) $data['body_text'], $globalIndexMap);
        $bodyComponent = [
            'type' => 'BODY',
            'text' => $bodyText,
        ];

        if (!empty($bodyVariables)) {
            $bodyComponent['example'] = [
                'body_text' => [[
                    ...array_map(fn (string $variable): string => (string) ($data['variable_examples'][$variable] ?? ''), $bodyVariables),
                ]],
            ];
        }

        $components[] = $bodyComponent;

        $footerRaw = $this->nullableTrim($data['footer_text'] ?? null);
        if ($footerRaw !== null) {
            [$footerText] = $this->transformTextWithMetaIndexes($footerRaw, $globalIndexMap);
            $components[] = [
                'type' => 'FOOTER',
                'text' => $footerText,
            ];
        }

        $buttons = (array) ($data['buttons'] ?? []);
        if (!empty($buttons)) {
            $buttonRows = [];

            foreach ($buttons as $button) {
                $type = (string) ($button['type'] ?? '');
                [$buttonText] = $this->transformTextWithMetaIndexes((string) ($button['text'] ?? ''), $globalIndexMap);

                if ($type === 'QUICK_REPLY') {
                    $buttonRows[] = [
                        'type' => 'QUICK_REPLY',
                        'text' => $buttonText,
                    ];

                    continue;
                }

                [$buttonUrl, $buttonUrlVariables] = $this->transformTextWithMetaIndexes((string) ($button['url'] ?? ''), $globalIndexMap);

                $buttonPayload = [
                    'type' => 'URL',
                    'text' => $buttonText,
                    'url' => $buttonUrl,
                ];

                if (!empty($buttonUrlVariables)) {
                    $buttonPayload['example'] = array_map(
                        fn (string $variable): string => (string) ($data['variable_examples'][$variable] ?? ''),
                        $buttonUrlVariables
                    );
                }

                $buttonRows[] = $buttonPayload;
            }

            if (!empty($buttonRows)) {
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => $buttonRows,
                ];
            }
        }

        return [
            'name' => (string) $data['name'],
            'category' => (string) $data['category'],
            'language' => (string) $data['language_code'],
            'components' => $components,
        ];
    }

    private function transformTextWithMetaIndexes(string $text, array &$globalIndexMap): array
    {
        $orderedVariables = [];

        $transformed = preg_replace_callback('/\{([a-z0-9_]+)\}/', function (array $matches) use (&$globalIndexMap, &$orderedVariables): string {
            $variable = $matches[1];

            if (!isset($globalIndexMap[$variable])) {
                $globalIndexMap[$variable] = count($globalIndexMap) + 1;
            }

            if (!in_array($variable, $orderedVariables, true)) {
                $orderedVariables[] = $variable;
            }

            return '{{' . $globalIndexMap[$variable] . '}}';
        }, $text) ?? $text;

        return [
            $transformed,
            $orderedVariables,
        ];
    }

    private function syncTemplateWithMeta(WhatsappCloudAccount $account, array $metaPayload, ?WhatsappCloudTemplate $template): array
    {
        if (!$account->business_account_id) {
            throw ValidationException::withMessages([
                'whatsapp_cloud_account_id' => ['A conta cloud selecionada não possui Business Account ID.'],
            ]);
        }

        $accessToken = trim((string) $account->access_token);
        if ($accessToken === '') {
            throw ValidationException::withMessages([
                'whatsapp_cloud_account_id' => ['A conta cloud selecionada não possui access token válido.'],
            ]);
        }

        $service = app(WhatsappCloudApiService::class);

        $mustCreate =
            !$template?->meta_template_id ||
            (int) $template->whatsapp_cloud_account_id !== (int) $account->id ||
            $template->template_name !== (string) $metaPayload['name'] ||
            $template->language_code !== (string) $metaPayload['language'];

        if ($mustCreate) {
            return $service->createMessageTemplate(
                (string) $account->business_account_id,
                $metaPayload,
                ['access_token' => $accessToken]
            );
        }

        return $service->editMessageTemplate(
            (string) $template->meta_template_id,
            [
                'category' => (string) $metaPayload['category'],
                'components' => $metaPayload['components'],
            ],
            ['access_token' => $accessToken]
        );
    }

    private function resolveTemplateStatus(array $syncResult, string $fallback): string
    {
        $body = $syncResult['body'] ?? [];
        if (!is_array($body)) {
            return $fallback;
        }

        $status = $body['status'] ?? ($body['message_status'] ?? null);
        if (is_string($status) && trim($status) !== '') {
            return Str::upper(trim($status));
        }

        if (isset($body['success']) && $body['success'] === true) {
            return Str::upper($fallback);
        }

        return Str::upper($fallback);
    }

    private function resolveMetaTemplateId(array $syncResult): ?string
    {
        $body = $syncResult['body'] ?? [];
        if (!is_array($body)) {
            return null;
        }

        $id = $body['id'] ?? null;
        if (!is_string($id)) {
            return null;
        }

        $id = trim($id);
        return $id !== '' ? $id : null;
    }

    private function findTemplateSnapshot(array $lookupResult, WhatsappCloudTemplate $template): ?array
    {
        $body = $lookupResult['body'] ?? [];
        if (!is_array($body)) {
            return null;
        }

        $rows = [];
        if (isset($body['data']) && is_array($body['data'])) {
            $rows = array_values(array_filter($body['data'], fn ($item) => is_array($item)));
        } elseif (array_is_list($body)) {
            $rows = array_values(array_filter($body, fn ($item) => is_array($item)));
        } elseif (!empty($body)) {
            $rows = [$body];
        }

        if (empty($rows)) {
            return null;
        }

        $metaTemplateId = trim((string) ($template->meta_template_id ?? ''));
        if ($metaTemplateId !== '') {
            foreach ($rows as $row) {
                if (trim((string) ($row['id'] ?? '')) === $metaTemplateId) {
                    return $row;
                }
            }
        }

        $templateName = trim((string) $template->template_name);
        $languageCode = Str::lower(trim((string) $template->language_code));
        $fallbackByName = null;

        foreach ($rows as $row) {
            if (trim((string) ($row['name'] ?? '')) !== $templateName) {
                continue;
            }

            if ($fallbackByName === null) {
                $fallbackByName = $row;
            }

            $rowLanguage = Str::lower(trim((string) ($row['language'] ?? '')));
            if ($languageCode !== '' && $rowLanguage !== '' && $rowLanguage === $languageCode) {
                return $row;
            }
        }

        return $fallbackByName ?: $rows[0];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private function refreshTemplateStatusSnapshot(WhatsappCloudTemplate $template): array
    {
        $template->loadMissing('account');

        $account = $template->account;
        if (!$account) {
            return [
                'ok' => false,
                'message' => 'Conta Cloud do modelo não encontrada.',
            ];
        }

        $businessAccountId = trim((string) ($account->business_account_id ?? ''));
        if ($businessAccountId === '') {
            $template->update([
                'last_synced_at' => now(),
                'last_sync_error' => 'Conta cloud sem Business Account ID configurado.',
            ]);

            return [
                'ok' => false,
                'message' => 'Não foi possível atualizar: Business Account ID ausente na conta.',
            ];
        }

        $accessToken = trim((string) ($account->access_token ?? ''));
        if ($accessToken === '') {
            $template->update([
                'last_synced_at' => now(),
                'last_sync_error' => 'Conta cloud sem access token configurado.',
            ]);

            return [
                'ok' => false,
                'message' => 'Não foi possível atualizar: access token ausente na conta.',
            ];
        }

        $service = app(WhatsappCloudApiService::class);
        $lookupResult = $service->getMessageTemplateByName(
            $businessAccountId,
            (string) $template->template_name,
            ['access_token' => $accessToken]
        );

        if (!empty($lookupResult['error'])) {
            $template->update([
                'last_synced_at' => now(),
                'last_sync_error' => $this->resolveMetaSyncError($lookupResult),
            ]);

            return [
                'ok' => false,
                'message' => 'Falha ao atualizar status na Meta.',
            ];
        }

        $snapshot = $this->findTemplateSnapshot($lookupResult, $template);
        if (!$snapshot) {
            $template->update([
                'last_synced_at' => now(),
                'last_sync_error' => 'Template não encontrado na Meta para esta conta.',
            ]);

            return [
                'ok' => false,
                'message' => 'Template não encontrado na Meta.',
            ];
        }

        $metaTemplateId = trim((string) ($snapshot['id'] ?? ''));
        $template->update([
            'status' => $this->resolveTemplateStatus(['body' => $snapshot], $template->status ?: 'PENDING'),
            'meta_template_id' => $metaTemplateId !== '' ? $metaTemplateId : $template->meta_template_id,
            'last_synced_at' => now(),
            'last_sync_error' => null,
        ]);

        return [
            'ok' => true,
            'message' => 'Status atualizado com sucesso.',
        ];
    }

    private function redirectToTemplateList(Request $request, WhatsappCloudTemplate $template): RedirectResponse
    {
        $params = [];
        $accountId = $request->integer('account_id');
        $conexaoId = $request->integer('conexao_id');

        if ($accountId > 0) {
            $params['account_id'] = $accountId;
        } else {
            $params['account_id'] = (int) $template->whatsapp_cloud_account_id;
        }

        if ($conexaoId > 0) {
            $params['conexao_id'] = $conexaoId;
        }

        return redirect()
            ->route('agencia.whatsapp-cloud.index', $params)
            ->withInput(['active_tab' => 'templates']);
    }

    private function resolveMetaSyncError(array $syncResult): string
    {
        $status = isset($syncResult['status']) ? (string) $syncResult['status'] : '';
        $statusLabel = trim($status) !== '' ? " ({$status})" : '';
        $body = $syncResult['body'] ?? [];

        if (is_array($body)) {
            $metaError = $body['error'] ?? null;
            if (is_array($metaError)) {
                $message = trim((string) ($metaError['message'] ?? ''));
                $details = trim((string) data_get($metaError, 'error_data.details', ''));
                $userMessage = trim((string) ($metaError['error_user_msg'] ?? ''));

                if ($details === '' && $userMessage !== '') {
                    $details = $userMessage;
                }

                if ($message !== '' && $details !== '') {
                    return "Meta API{$statusLabel}: {$message} - {$details}";
                }

                if ($message !== '') {
                    return "Meta API{$statusLabel}: {$message}";
                }
            }

            $message = trim((string) ($body['message'] ?? ''));
            if ($message !== '') {
                return "Meta API{$statusLabel}: {$message}";
            }
        }

        return 'Falha ao sincronizar o template na Meta. Revise os campos e tente novamente.';
    }

    private function isTemplateUrlValid(string $url): bool
    {
        $candidate = preg_replace('/\{[a-z0-9_]+\}/', 'example', $url) ?? $url;

        if (!filter_var($candidate, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower((string) parse_url($candidate, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true);
    }

    private function nullableTrim(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function ensureAccountOwnership(WhatsappCloudAccount $account, int $userId): void
    {
        if ((int) $account->user_id !== $userId) {
            abort(403);
        }
    }

    private function ensureTemplateOwnership(WhatsappCloudTemplate $template, int $userId): void
    {
        if ((int) $template->user_id !== $userId) {
            abort(403);
        }
    }

    private function ensureCampaignOwnership(WhatsappCloudCampaign $campaign, int $userId): void
    {
        if ((int) $campaign->user_id !== $userId) {
            abort(403);
        }
    }

    private function findOwnedAccount(int $accountId, int $userId): WhatsappCloudAccount
    {
        return WhatsappCloudAccount::query()
            ->where('user_id', $userId)
            ->findOrFail($accountId);
    }

    private function resolveOwnedTemplateConexao(mixed $conexaoId, int $userId, int $accountId): ?Conexao
    {
        if (!$conexaoId) {
            return null;
        }

        $conexao = $this->ownedCloudConexoesQuery($userId)->findOrFail((int) $conexaoId);

        if ($conexao->whatsapp_cloud_account_id !== null && (int) $conexao->whatsapp_cloud_account_id !== $accountId) {
            throw ValidationException::withMessages([
                'conexao_id' => ['A conexão selecionada já está vinculada a outra conta cloud.'],
            ]);
        }

        if ($conexao->whatsapp_cloud_account_id === null) {
            $conexao->whatsapp_cloud_account_id = $accountId;
            $conexao->save();
        }

        return $conexao;
    }

    private function generateUniqueUserToken(string $column, string $prefix): string
    {
        do {
            $token = $prefix . Str::lower(Str::random(48));
        } while (User::query()->where($column, $token)->exists());

        return $token;
    }

    private function clearOtherDefaultAccounts(int $userId, int $currentAccountId): void
    {
        WhatsappCloudAccount::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $currentAccountId)
            ->update(['is_default' => false]);
    }

    private function ownedCloudConexoesQuery(int $userId)
    {
        return Conexao::query()
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $userId))
            ->whereHas('whatsappApi', fn ($query) => $query->where('slug', 'whatsapp_cloud'));
    }

    private function buildCampaignLeadsQuery(int $clienteId, array $tagIds)
    {
        $query = ClienteLead::query()
            ->where('cliente_id', $clienteId)
            ->whereNotNull('phone')
            ->whereRaw("TRIM(phone) <> ''");

        if (!empty($tagIds)) {
            $query->whereHas('tags', function ($builder) use ($tagIds): void {
                $builder->whereIn('tags.id', $tagIds);
            });
        }

        return $query;
    }

    private function resolveCampaignTagIds(array $tagIds, int $userId, int $clienteId): array
    {
        $normalized = collect($tagIds)
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($normalized)) {
            return [];
        }

        $resolved = Tag::query()
            ->where('user_id', $userId)
            ->whereIn('id', $normalized)
            ->where(function ($query) use ($clienteId): void {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteId);
            })
            ->pluck('id')
            ->map(fn ($value) => (int) $value)
            ->all();

        if (count($resolved) !== count($normalized)) {
            throw ValidationException::withMessages([
                'tag_ids' => ['Uma ou mais tags selecionadas são inválidas para o cliente informado.'],
            ]);
        }

        return $resolved;
    }
}
