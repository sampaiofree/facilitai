<?php

namespace App\Services;

use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\WhatsappCloudCustomField;
use App\Models\WhatsappCloudTemplate;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Support\Str;

class WhatsappCloudTemplateSendService
{
    public function __construct(
        private readonly WhatsappCloudApiService $whatsappCloudApiService,
        private readonly PhoneNumberNormalizer $phoneNumberNormalizer
    ) {
    }

    /**
     * @param array{
     *   user_id:int,
     *   conexao:Conexao,
     *   template:WhatsappCloudTemplate,
     *   lead:ClienteLead,
     *   template_variables?:array<string,mixed>
     * } $payload
     * @return array{
     *   ok:bool,
     *   error_code:?string,
     *   message:string,
     *   response:?array,
     *   phone:?string,
     *   resolved_variables:array<string,string>,
     *   missing_variables:array<int,string>,
     *   rendered_message:?string
     * }
     */
    public function sendToLead(array $payload): array
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $conexao = $payload['conexao'] ?? null;
        $template = $payload['template'] ?? null;
        $lead = $payload['lead'] ?? null;
        $rawValues = is_array($payload['template_variables'] ?? null)
            ? $payload['template_variables']
            : [];

        if (!$conexao instanceof Conexao || !$template instanceof WhatsappCloudTemplate || !$lead instanceof ClienteLead) {
            return $this->error(
                'invalid_payload',
                'Payload inválido para envio de template Cloud.'
            );
        }

        $conexao->loadMissing(['whatsappApi', 'whatsappCloudAccount']);

        if (!$this->isWhatsappCloudConexao($conexao)) {
            return $this->error(
                'invalid_conexao_type',
                'A conexão selecionada não é do tipo WhatsApp Cloud.'
            );
        }

        if ((int) $conexao->cliente_id !== (int) $lead->cliente_id) {
            return $this->error(
                'invalid_conexao_lead_cliente',
                'A conexão selecionada não pertence ao cliente deste lead.'
            );
        }

        $accountId = (int) ($conexao->whatsapp_cloud_account_id ?? 0);
        if ($accountId <= 0) {
            return $this->error(
                'cloud_account_missing',
                'Conexão Cloud sem conta vinculada.'
            );
        }

        $templateStatus = Str::upper(trim((string) ($template->status ?? '')));
        if (!in_array($templateStatus, ['APPROVED', 'ACTIVE'], true)) {
            return $this->error(
                'template_not_approved',
                'Somente modelos aprovados podem ser enviados fora da janela de 24h.'
            );
        }

        if ((int) $template->whatsapp_cloud_account_id !== $accountId) {
            return $this->error(
                'template_account_mismatch',
                'O modelo selecionado não pertence à conta Cloud desta conexão.'
            );
        }

        if ($template->conexao_id !== null && (int) $template->conexao_id !== (int) $conexao->id) {
            return $this->error(
                'template_conexao_mismatch',
                'O modelo selecionado está restrito a outra conexão.'
            );
        }

        $phone = $this->phoneNumberNormalizer->normalizeLeadPhone((string) ($lead->phone ?? ''));
        if ($phone === null) {
            return $this->error(
                'lead_phone_invalid',
                'Lead sem telefone válido para envio.'
            );
        }

        $resolvedOptions = $this->resolveCloudSendOptionsFromConexao($conexao);
        if (!$resolvedOptions['ok']) {
            return $this->error(
                'cloud_options_invalid',
                (string) $resolvedOptions['message']
            );
        }

        [$variableValues, $missingVariables] = $this->resolveTemplateVariableValuesForLead(
            $template,
            $lead,
            $userId,
            $rawValues
        );

        if (!empty($missingVariables)) {
            return [
                'ok' => false,
                'error_code' => 'template_missing_variables',
                'message' => 'Preencha as variáveis obrigatórias do modelo: ' . implode(', ', $missingVariables) . '.',
                'response' => null,
                'phone' => $phone,
                'resolved_variables' => $variableValues,
                'missing_variables' => $missingVariables,
                'rendered_message' => $this->renderTemplateMessage($template, $variableValues),
            ];
        }

        $components = $this->buildTemplateSendComponents($template, $variableValues);
        $response = $this->whatsappCloudApiService->sendTemplateUtility(
            $phone,
            (string) $template->template_name,
            [],
            array_merge(
                $resolvedOptions['options'],
                [
                    'language_code' => (string) $template->language_code,
                    'components' => $components,
                ]
            )
        );

        if (!empty($response['error'])) {
            return [
                'ok' => false,
                'error_code' => 'cloud_api_error',
                'message' => $this->resolveCloudApiErrorMessage($response),
                'response' => $response,
                'phone' => $phone,
                'resolved_variables' => $variableValues,
                'missing_variables' => [],
                'rendered_message' => $this->renderTemplateMessage($template, $variableValues),
            ];
        }

        return [
            'ok' => true,
            'error_code' => null,
            'message' => 'Modelo enviado com sucesso.',
            'response' => $response,
            'phone' => $phone,
            'resolved_variables' => $variableValues,
            'missing_variables' => [],
            'rendered_message' => $this->renderTemplateMessage($template, $variableValues),
        ];
    }

    private function error(string $errorCode, string $message): array
    {
        return [
            'ok' => false,
            'error_code' => $errorCode,
            'message' => $message,
            'response' => null,
            'phone' => null,
            'resolved_variables' => [],
            'missing_variables' => [],
            'rendered_message' => null,
        ];
    }

    private function resolveCloudSendOptionsFromConexao(Conexao $conexao): array
    {
        $accessToken = trim((string) (
            $conexao->whatsappCloudAccount?->access_token
            ?? $conexao->whatsapp_api_key
            ?? ''
        ));

        if ($accessToken === '') {
            return [
                'ok' => false,
                'message' => 'Conexão Cloud sem access token válido.',
                'options' => [],
            ];
        }

        $phoneNumberId = trim((string) (
            $conexao->whatsappCloudAccount?->phone_number_id
            ?? $conexao->phone
            ?? ''
        ));

        if ($phoneNumberId === '' || !preg_match('/^\d+$/', $phoneNumberId)) {
            return [
                'ok' => false,
                'message' => 'Conexão Cloud sem Phone Number ID válido.',
                'options' => [],
            ];
        }

        return [
            'ok' => true,
            'message' => null,
            'options' => [
                'access_token' => $accessToken,
                'phone_number_id' => $phoneNumberId,
            ],
        ];
    }

    private function resolveCloudApiErrorMessage(array $response): string
    {
        $body = $response['body'] ?? [];
        if (is_array($body)) {
            $metaError = $body['error'] ?? null;
            if (is_array($metaError)) {
                $message = trim((string) ($metaError['message'] ?? ''));
                $details = trim((string) data_get($metaError, 'error_data.details', ''));

                if ($message !== '' && $details !== '') {
                    return "{$message} - {$details}";
                }

                if ($message !== '') {
                    return $message;
                }
            }

            $message = trim((string) ($body['message'] ?? ''));
            if ($message !== '') {
                return $message;
            }
        }

        return 'Falha ao enviar modelo pela WhatsApp Cloud API.';
    }

    private function resolveTemplateVariableValuesForLead(
        WhatsappCloudTemplate $template,
        ClienteLead $clienteLead,
        int $userId,
        array $rawValues
    ): array {
        $bodyVariables = $this->extractPlaceholderVariables((string) ($template->body_text ?? ''));
        $buttonVariables = [];
        foreach ((array) ($template->buttons ?? []) as $button) {
            if (!is_array($button)) {
                continue;
            }

            $type = Str::upper(trim((string) ($button['type'] ?? '')));
            if ($type !== 'URL') {
                continue;
            }

            $buttonVariables = array_merge(
                $buttonVariables,
                $this->extractPlaceholderVariables((string) ($button['url'] ?? ''))
            );
        }

        $variables = collect([
            ...(array) ($template->variables ?? []),
            ...$bodyVariables,
            ...$buttonVariables,
        ])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($variables)) {
            return [[], []];
        }

        $fields = WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->whereIn('name', $variables)
            ->where(function ($query) use ($clienteLead) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteLead->cliente_id);
            })
            ->orderByRaw('CASE WHEN cliente_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->get(['id', 'name', 'sample_value']);

        $fieldLeadValuesById = $clienteLead->customFieldValues()
            ->whereIn('whatsapp_cloud_custom_field_id', $fields->pluck('id')->all())
            ->get(['whatsapp_cloud_custom_field_id', 'value'])
            ->mapWithKeys(fn ($row) => [
                (int) $row->whatsapp_cloud_custom_field_id => trim((string) ($row->value ?? '')),
            ])
            ->all();

        $fieldDefaults = [];
        $fieldLeadValues = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field->name, $fieldDefaults)) {
                $fieldDefaults[$field->name] = trim((string) ($field->sample_value ?? ''));
            }
            if (!array_key_exists($field->name, $fieldLeadValues)) {
                $fieldLeadValues[$field->name] = $fieldLeadValuesById[(int) $field->id] ?? '';
            }
        }

        $leadFallbacks = [
            'name' => trim((string) ($clienteLead->name ?? '')),
            'nome' => trim((string) ($clienteLead->name ?? '')),
            'nome_cliente' => trim((string) ($clienteLead->name ?? '')),
            'phone' => trim((string) ($clienteLead->phone ?? '')),
            'telefone' => trim((string) ($clienteLead->phone ?? '')),
            'whatsapp' => trim((string) ($clienteLead->phone ?? '')),
            'info' => trim((string) ($clienteLead->info ?? '')),
            'informacoes' => trim((string) ($clienteLead->info ?? '')),
        ];

        $resolved = [];
        $missing = [];

        foreach ($variables as $name) {
            $manual = isset($rawValues[$name]) ? trim((string) $rawValues[$name]) : '';
            $leadCustom = $fieldLeadValues[$name] ?? '';
            $fallback = $leadFallbacks[$name] ?? '';
            $default = $fieldDefaults[$name] ?? '';
            $value = $manual !== '' ? $manual : ($leadCustom !== '' ? $leadCustom : ($fallback !== '' ? $fallback : $default));

            if ($value === '') {
                $missing[] = $name;
                continue;
            }

            $resolved[$name] = $value;
        }

        return [$resolved, $missing];
    }

    private function buildTemplateSendComponents(WhatsappCloudTemplate $template, array $variableValues): array
    {
        $components = [];

        $bodyVariables = $this->extractPlaceholderVariables((string) ($template->body_text ?? ''));
        if (!empty($bodyVariables)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $name) => ['type' => 'text', 'text' => (string) ($variableValues[$name] ?? '')],
                    $bodyVariables
                ),
            ];
        }

        $buttons = is_array($template->buttons) ? $template->buttons : [];
        foreach ($buttons as $index => $button) {
            if (!is_array($button)) {
                continue;
            }

            $type = Str::upper(trim((string) ($button['type'] ?? '')));
            if ($type !== 'URL') {
                continue;
            }

            $urlVariables = $this->extractPlaceholderVariables((string) ($button['url'] ?? ''));
            if (empty($urlVariables)) {
                continue;
            }

            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => (string) $index,
                'parameters' => array_map(
                    fn (string $name) => ['type' => 'text', 'text' => (string) ($variableValues[$name] ?? '')],
                    $urlVariables
                ),
            ];
        }

        return $components;
    }

    private function extractPlaceholderVariables(string $text): array
    {
        if ($text === '') {
            return [];
        }

        preg_match_all('/\{([a-z0-9_]+)\}/', $text, $matches);

        $ordered = [];
        foreach (($matches[1] ?? []) as $name) {
            if (!in_array($name, $ordered, true)) {
                $ordered[] = $name;
            }
        }

        return $ordered;
    }

    private function isWhatsappCloudConexao(Conexao $conexao): bool
    {
        return Str::lower(trim((string) ($conexao->whatsappApi?->slug ?? ''))) === 'whatsapp_cloud';
    }

    public function renderTemplateMessage(WhatsappCloudTemplate $template, array $variableValues): string
    {
        $body = $this->renderText((string) ($template->body_text ?? ''), $variableValues);
        $footer = $this->renderText((string) ($template->footer_text ?? ''), $variableValues);
        $buttons = is_array($template->buttons) ? $template->buttons : [];

        $lines = [];
        $lines[] = "[Template: {$template->template_name}]";

        if ($body !== '') {
            $lines[] = 'Mensagem:';
            $lines[] = $body;
        }

        if ($footer !== '') {
            $lines[] = 'Rodapé:';
            $lines[] = $footer;
        }

        if (!empty($buttons)) {
            $lines[] = 'Botões:';
            foreach ($buttons as $button) {
                if (!is_array($button)) {
                    continue;
                }

                $text = $this->renderText(trim((string) ($button['text'] ?? '')), $variableValues);
                $type = Str::upper(trim((string) ($button['type'] ?? 'QUICK_REPLY')));
                if ($text === '') {
                    continue;
                }

                if ($type === 'URL') {
                    $url = $this->renderText(trim((string) ($button['url'] ?? '')), $variableValues);
                    $lines[] = $url !== ''
                        ? "- {$text} -> {$url}"
                        : "- {$text}";
                    continue;
                }

                $lines[] = "- {$text}";
            }
        }

        return trim(implode("\n", $lines));
    }

    private function renderText(string $text, array $variables): string
    {
        if ($text === '') {
            return '';
        }

        return (string) preg_replace_callback('/\{([a-z0-9_]+)\}/', function (array $matches) use ($variables) {
            $name = (string) ($matches[1] ?? '');
            $value = trim((string) ($variables[$name] ?? ''));

            return $value !== '' ? $value : $matches[0];
        }, $text);
    }
}
