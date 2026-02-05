<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AsaasService;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('conexoes')
            ->with('asaasWebhooks')
            ->with('plan')
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
}
