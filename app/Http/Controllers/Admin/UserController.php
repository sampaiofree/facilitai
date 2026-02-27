<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AsaasWebhook;
use App\Services\AsaasService;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('conexoes')
            ->with('asaasWebhooks')
            ->with('plan')
            ->with(['clientes' => function ($query) {
                $query->orderBy('nome');
            }])
            ->latest()
            ->get();

        $plans = Plan::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'cpf_cnpj' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'customer_asaas_id' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'mobile_phone' => $data['mobile_phone'] ?? null,
            'customer_asaas_id' => $data['customer_asaas_id'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
            'password' => $data['password'],
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return redirect()
            ->route('adm.users.index')
            ->with('success', 'Usuário criado com sucesso.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'cpf_cnpj' => ['nullable', 'string', 'max:50'],
            'mobile_phone' => ['nullable', 'string', 'max:20'],
            'customer_asaas_id' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf_cnpj' => $data['cpf_cnpj'] ?? null,
            'mobile_phone' => $data['mobile_phone'] ?? null,
            'customer_asaas_id' => $data['customer_asaas_id'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
            'is_admin' => $request->boolean('is_admin'),
        ];

        if (!empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return redirect()
            ->route('adm.users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('adm.users.index')
            ->with('success', 'UsuÃ¡rio removido com sucesso.');
    }

    public function createAsaasCustomer(User $user)
    {
        if (empty($user->cpf_cnpj)) {
            return response()->json([
                'error' => true,
                'message' => 'CPF/CNPJ obrigatÃ³rio para criar customer.',
            ], 422);
        }

        if (!empty($user->customer_asaas_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Customer jÃ¡ existente para este usuÃ¡rio.',
            ], 409);
        }

        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'cpfCnpj' => $user->cpf_cnpj,
        ];

        if (!empty($user->mobile_phone)) {
            $payload['mobilePhone'] = $user->mobile_phone;
        }

        $asaas = new AsaasService();
        $response = $asaas->createCustomer($payload);

        if (empty($response) || !empty($response['error'])) {
            return response()->json([
                'error' => true,
                'message' => 'Falha ao criar customer no Asaas.',
                'response' => $response,
            ], 502);
        }

        $customerId = $response['id'] ?? null;
        if (!$customerId) {
            return response()->json([
                'error' => true,
                'message' => 'Resposta do Asaas sem id.',
                'response' => $response,
            ], 502);
        }

        $user->update([
            'customer_asaas_id' => $customerId,
        ]);

        return response()->json([
            'ok' => true,
            'customer_id' => $customerId,
        ]);
    }

    public function createAsaasSubscription(Request $request, User $user)
    {
        $data = $request->validate([
            'next_due_date' => ['required', 'date'],
            'billing_type' => ['required', 'in:UNDEFINED,BOLETO,CREDIT_CARD,PIX'],
            'cycle' => ['required', 'in:MONTHLY,YEARLY'],
        ]);

        if (empty($user->customer_asaas_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Customer Asaas obrigatório para criar assinatura.',
            ], 422);
        }

        if (!empty($user->asaas_sub)) {
            return response()->json([
                'error' => true,
                'message' => 'Assinatura já existente para este usuário.',
            ], 409);
        }

        $plan = $user->plan;
        if (!$plan || $plan->price_cents === null) {
            return response()->json([
                'error' => true,
                'message' => 'Plano sem valor definido para criar assinatura.',
            ], 422);
        }

        $payload = [
            'customer' => $user->customer_asaas_id,
            'billingType' => $data['billing_type'],
            'value' => $plan->price_cents,
            'nextDueDate' => $data['next_due_date'],
            'cycle' => $data['cycle'],
        ];

        $asaas = new AsaasService();
        $response = $asaas->createSubscription($payload);

        if (empty($response) || !empty($response['error'])) {
            return response()->json([
                'error' => true,
                'message' => 'Falha ao criar assinatura no Asaas.',
                'response' => $response,
            ], 502);
        }

        $subscriptionId = $response['id'] ?? null;
        if (!$subscriptionId) {
            return response()->json([
                'error' => true,
                'message' => 'Resposta do Asaas sem id da assinatura.',
                'response' => $response,
            ], 502);
        }

        $user->update([
            'asaas_sub' => $subscriptionId,
        ]);

        return response()->json([
            'ok' => true,
            'subscription_id' => $subscriptionId,
        ]);
    }

    public function updateAsaasSubscription(Request $request, User $user)
    {
        $data = $request->validate([
            'value' => ['required'],
        ]);

        if (empty($user->asaas_sub)) {
            return response()->json([
                'error' => true,
                'message' => 'Assinatura Asaas não encontrada para este usuário.',
            ], 422);
        }

        $value = $this->normalizeMoneyValue($data['value']);
        if ($value === null || $value <= 0) {
            return response()->json([
                'error' => true,
                'message' => 'Valor da assinatura inválido.',
            ], 422);
        }

        $asaas = new AsaasService();
        $response = $asaas->updateSubscription($user->asaas_sub, [
            'value' => $value,
            'updatePendingPayments' => true,
        ]);

        if (empty($response) || !empty($response['error'])) {
            return response()->json([
                'error' => true,
                'message' => 'Falha ao atualizar assinatura no Asaas.',
                'response' => $response,
                'asaas_response' => $response['response'] ?? $response,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Assinatura atualizada com sucesso. Cobranças pendentes foram atualizadas.',
            'subscription_id' => $response['id'] ?? $user->asaas_sub,
            'value' => $response['value'] ?? $value,
            'asaas_response' => $response,
        ]);
    }

    public function getAsaasSubscriptionLink(User $user)
    {
        if (empty($user->asaas_sub)) {
            return response()->json([
                'error' => true,
                'message' => 'Assinatura Asaas não encontrada para este usuário.',
            ], 422);
        }

        $asaas = new AsaasService();
        $response = $asaas->getSubscriptionPaymentLink($user->asaas_sub, ['status' => 'PENDING']);

        if (!$response || !empty($response['error'])) {
            $fallback = $asaas->getSubscriptionPaymentLink($user->asaas_sub);
            if ($fallback && empty($fallback['error']) && !empty($fallback['invoice_url'])) {
                return response()->json([
                    'ok' => true,
                    'url' => $fallback['invoice_url'],
                    'payment_id' => $fallback['payment_id'] ?? null,
                ]);
            }

            return response()->json([
                'error' => true,
                'message' => $response['message'] ?? 'Falha ao obter link de cobrança.',
                'response' => $response,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'url' => $response['invoice_url'] ?? null,
            'payment_id' => $response['payment_id'] ?? null,
        ]);
    }

    public function getAsaasSubscriptionDetails(User $user)
    {
        if (empty($user->asaas_sub)) {
            return response()->json([
                'error' => true,
                'message' => 'Assinatura Asaas nao encontrada para este usuario.',
            ], 422);
        }

        $asaas = new AsaasService();
        $response = $asaas->getSubscription($user->asaas_sub);

        Log::channel('asaas')->info('Resposta ao recuperar assinatura Asaas', [
            'user_id' => $user->id,
            'subscription_id' => $user->asaas_sub,
            'response_body' => $response,
        ]);

        if (empty($response) || !empty($response['error'])) {
            return response()->json([
                'error' => true,
                'message' => 'Falha ao recuperar assinatura no Asaas.',
                'response' => $response,
                'asaas_response' => $response['response'] ?? $response,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'subscription' => $this->normalizeAsaasSubscriptionDetails($response),
            'asaas_response' => $response,
        ]);
    }

    public function syncAsaasSubscriptionPayments(User $user)
    {
        if (empty($user->asaas_sub)) {
            return response()->json([
                'error' => true,
                'message' => 'Assinatura Asaas nao encontrada para este usuario.',
            ], 422);
        }

        $asaas = new AsaasService();
        $payments = [];
        $offset = 0;
        $limit = 100;
        $hasMore = true;
        $pagesFetched = 0;
        $totalCount = null;

        while ($hasMore) {
            $query = [
                'offset' => $offset,
                'limit' => $limit,
            ];
            $response = $asaas->listSubscriptionPayments($user->asaas_sub, $query);

            Log::channel('asaas')->info('Resposta ao listar cobrancas da assinatura Asaas', [
                'user_id' => $user->id,
                'subscription_id' => $user->asaas_sub,
                'query' => $query,
                'response_body' => $response,
            ]);

            if (empty($response) || !empty($response['error'])) {
                return response()->json([
                    'error' => true,
                    'message' => 'Falha ao listar cobrancas da assinatura no Asaas.',
                    'response' => $response,
                    'asaas_response' => $response['response'] ?? $response,
                ], 502);
            }

            $currentPagePayments = $response['data'] ?? [];
            if (!empty($currentPagePayments)) {
                $payments = array_merge($payments, $currentPagePayments);
            }

            $hasMore = (bool) ($response['hasMore'] ?? false);
            $totalCount = $response['totalCount'] ?? $totalCount;
            $offset += $limit;
            $pagesFetched++;
        }

        $created = 0;
        $updated = 0;

        foreach ($payments as $payment) {
            $paymentId = $payment['id'] ?? null;
            if (!$paymentId) {
                continue;
            }

            $attributes = $this->mapAsaasPaymentToWebhookAttributes(
                $payment,
                $user->asaas_sub,
                $user->customer_asaas_id
            );
            $existingWebhook = AsaasWebhook::query()
                ->where('payment_id', $paymentId)
                ->first();

            if ($existingWebhook) {
                $existingWebhook->fill($attributes);
                $existingWebhook->save();
                $updated++;
                continue;
            }

            AsaasWebhook::create(array_merge($attributes, [
                'webhook_id' => $this->buildSyncWebhookId($paymentId),
            ]));
            $created++;
        }

        $webhooks = $user->asaasWebhooks()
            ->latest()
            ->get()
            ->map(function (AsaasWebhook $hook) {
                return [
                    'id' => $hook->id,
                    'event_type' => $hook->event_type,
                    'status' => $hook->status,
                    'value' => $hook->value,
                    'billing_type' => $hook->billing_type,
                    'payment_id' => $hook->payment_id,
                    'customer_id' => $hook->customer_id,
                    'external_reference' => $hook->external_reference,
                    'payment_at' => $hook->payment_at?->format('d/m/Y'),
                    'confirmed_at' => $hook->confirmed_at?->format('d/m/Y'),
                    'created_at' => $hook->created_at?->format('d/m/Y H:i'),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'message' => 'Cobrancas sincronizadas com sucesso.',
            'summary' => [
                'created' => $created,
                'updated' => $updated,
                'total' => count($payments),
            ],
            'asaas_webhooks' => $webhooks,
            'asaas_response' => [
                'pages_fetched' => $pagesFetched,
                'total_count' => $totalCount,
                'fetched_count' => count($payments),
            ],
        ]);
    }

    private function mapAsaasPaymentToWebhookAttributes(
        array $payment,
        string $subscriptionId,
        ?string $fallbackCustomerId = null
    ): array
    {
        return [
            'event_type' => 'PAYMENT_SYNC',
            'webhook_created_at' => $payment['dateCreated'] ?? now()->toDateTimeString(),
            'payment_id' => $payment['id'] ?? null,
            'payment_created_at' => $payment['dateCreated'] ?? null,
            'customer_id' => $payment['customer'] ?? $fallbackCustomerId,
            'value' => $payment['value'] ?? null,
            'description' => $payment['description'] ?? null,
            'billing_type' => $payment['billingType'] ?? null,
            'confirmed_at' => $payment['confirmedDate'] ?? null,
            'status' => $payment['status'] ?? null,
            'payment_at' => $payment['paymentDate'] ?? null,
            'client_payment_at' => $payment['clientPaymentDate'] ?? null,
            'invoice_url' => $payment['invoiceUrl'] ?? null,
            'external_reference' => $payment['externalReference'] ?? null,
            'transaction_receipt_url' => $payment['transactionReceiptUrl'] ?? null,
            'nosso_numero' => $payment['nossoNumero'] ?? null,
            'payload' => [
                'source' => 'subscription_payments_sync',
                'subscription_id' => $subscriptionId,
                'payment' => $payment,
            ],
        ];
    }

    private function buildSyncWebhookId(string $paymentId): string
    {
        return 'sync_' . substr(hash('sha256', $paymentId), 0, 24);
    }

    private function normalizeAsaasSubscriptionDetails(array $subscription): array
    {
        return [
            'id' => $subscription['id'] ?? null,
            'object' => $subscription['object'] ?? null,
            'status' => $subscription['status'] ?? null,
            'customer' => $subscription['customer'] ?? null,
            'value' => $subscription['value'] ?? null,
            'billing_type' => $subscription['billingType'] ?? null,
            'cycle' => $subscription['cycle'] ?? null,
            'date_created' => $subscription['dateCreated'] ?? null,
            'next_due_date' => $subscription['nextDueDate'] ?? null,
            'end_date' => $subscription['endDate'] ?? null,
            'description' => $subscription['description'] ?? null,
            'payment_link' => $subscription['paymentLink'] ?? null,
            'deleted' => $subscription['deleted'] ?? null,
            'max_payments' => $subscription['maxPayments'] ?? null,
            'external_reference' => $subscription['externalReference'] ?? null,
            'checkout_session' => $subscription['checkoutSession'] ?? null,
            'split_count' => is_array($subscription['split'] ?? null) ? count($subscription['split']) : 0,
            'fine' => $subscription['fine'] ?? null,
            'interest' => $subscription['interest'] ?? null,
            'discount' => $subscription['discount'] ?? null,
        ];
    }

    private function normalizeMoneyValue(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        if (!is_string($value)) {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', trim($value));
        if ($normalized === '') {
            return null;
        }
        $normalized = preg_replace('/[^\d,.-]/', '', $normalized);
        if ($normalized === '') {
            return null;
        }

        $hasComma = str_contains($normalized, ',');
        $hasDot = str_contains($normalized, '.');

        if ($hasComma && $hasDot) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($hasComma) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (!is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }
}
