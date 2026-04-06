<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\KommoAccount;
use App\Services\KommoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AgenciaKommoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $accounts = KommoAccount::query()
            ->where('user_id', $user->id)
            ->with('cliente')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $clientes = Cliente::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('agencia.kommo.index', [
            'accounts' => $accounts,
            'clientes' => $clientes,
        ]);
    }

    public function validateConnection(Request $request, KommoService $kommoService): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'kommo_account_id' => ['nullable', 'integer'],
            'subdomain' => ['required', 'string', 'max:191'],
            'access_token' => ['nullable', 'string'],
        ]);

        $account = null;
        if (!empty($data['kommo_account_id'])) {
            $account = KommoAccount::query()->findOrFail((int) $data['kommo_account_id']);
            $this->ensureOwnership($user->id, $account);
        }

        $subdomain = $kommoService->normalizeSubdomain((string) $data['subdomain']);
        $token = trim((string) ($data['access_token'] ?? ''));

        if ($token === '' && $account) {
            $token = (string) $account->access_token;
        }

        $result = $kommoService->validateAccount($subdomain, $token);
        $status = $result['ok'] ? 200 : (($result['status'] ?? 422) > 0 ? (int) $result['status'] : 422);

        return response()->json([
            'ok' => (bool) $result['ok'],
            'message' => $result['message'],
            'account_id' => $result['account_id'],
            'account_name' => $result['account_name'],
            'subdomain' => $result['subdomain'] ?? $subdomain,
            'status' => $result['status'],
        ], $status);
    }

    public function pipelines(Request $request, KommoService $kommoService): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'kommo_account_id' => ['nullable', 'integer'],
            'subdomain' => ['required', 'string', 'max:191'],
            'access_token' => ['nullable', 'string'],
        ]);

        $account = $this->resolveContextAccount($user->id, $data['kommo_account_id'] ?? null);
        $token = $this->resolveRequestToken((string) ($data['access_token'] ?? ''), $account);
        $result = $kommoService->listPipelines((string) $data['subdomain'], $token);
        $status = $result['ok'] ? 200 : (($result['status'] ?? 422) > 0 ? (int) $result['status'] : 422);

        return response()->json([
            'ok' => (bool) $result['ok'],
            'message' => $result['message'],
            'pipelines' => $result['pipelines'] ?? [],
            'subdomain' => $result['subdomain'] ?? $kommoService->normalizeSubdomain((string) $data['subdomain']),
            'status' => $result['status'] ?? null,
        ], $status);
    }

    public function statuses(Request $request, KommoService $kommoService): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'kommo_account_id' => ['nullable', 'integer'],
            'subdomain' => ['required', 'string', 'max:191'],
            'access_token' => ['nullable', 'string'],
            'pipeline_id' => ['required', 'string', 'max:50'],
        ]);

        $account = $this->resolveContextAccount($user->id, $data['kommo_account_id'] ?? null);
        $token = $this->resolveRequestToken((string) ($data['access_token'] ?? ''), $account);
        $result = $kommoService->listStatuses((string) $data['subdomain'], $token, (string) $data['pipeline_id']);
        $status = $result['ok'] ? 200 : (($result['status'] ?? 422) > 0 ? (int) $result['status'] : 422);

        return response()->json([
            'ok' => (bool) $result['ok'],
            'message' => $result['message'],
            'statuses' => $result['statuses'] ?? [],
            'pipeline_id' => $result['pipeline_id'] ?? (string) $data['pipeline_id'],
            'subdomain' => $result['subdomain'] ?? $kommoService->normalizeSubdomain((string) $data['subdomain']),
            'status' => $result['status'] ?? null,
        ], $status);
    }

    public function store(Request $request, KommoService $kommoService): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'subdomain' => ['required', 'string', 'max:191'],
            'access_token' => ['required', 'string'],
            'pipeline_id' => ['required', 'string', 'max:50'],
            'status_id' => ['required', 'string', 'max:50'],
            'form_mode' => ['nullable', 'string'],
            'kommo_account_context_id' => ['nullable', 'integer'],
        ]);

        $cliente = $this->resolveOwnedCliente($user->id, (int) $data['cliente_id']);
        $name = $this->normalizeAndEnsureUniqueName((string) $data['name'], $user->id);
        $subdomain = $this->normalizeSubdomainOrFail($kommoService, (string) $data['subdomain']);
        $token = trim((string) $data['access_token']);

        $validation = $this->validateOrFail($kommoService, $subdomain, $token);
        $destination = $this->resolveDestinationOrFail(
            $kommoService,
            $subdomain,
            $token,
            (string) $data['pipeline_id'],
            (string) $data['status_id']
        );

        KommoAccount::query()->create([
            'user_id' => $user->id,
            'cliente_id' => $cliente->id,
            'name' => $name,
            'subdomain' => $subdomain,
            'access_token' => $token,
            'kommo_account_id' => $validation['account_id'],
            'kommo_account_name' => $validation['account_name'],
            'pipeline_id' => $destination['pipeline_id'],
            'pipeline_name' => $destination['pipeline_name'],
            'status_id' => $destination['status_id'],
            'status_name' => $destination['status_name'],
            'last_verified_at' => Carbon::now(),
            'last_verification_error' => null,
        ]);

        return redirect()
            ->route('agencia.kommo.index')
            ->with('success', 'Integração Kommo criada com sucesso.');
    }

    public function update(Request $request, KommoAccount $kommoAccount, KommoService $kommoService): RedirectResponse
    {
        $user = $request->user();
        $this->ensureOwnership($user->id, $kommoAccount);

        $data = $request->validate([
            'cliente_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:191'],
            'subdomain' => ['required', 'string', 'max:191'],
            'access_token' => ['nullable', 'string'],
            'pipeline_id' => ['required', 'string', 'max:50'],
            'status_id' => ['required', 'string', 'max:50'],
            'form_mode' => ['nullable', 'string'],
            'kommo_account_context_id' => ['nullable', 'integer'],
        ]);

        $cliente = $this->resolveOwnedCliente($user->id, (int) $data['cliente_id']);
        $this->ensureImmutableFields($kommoAccount, $cliente->id, (string) $data['name']);

        $subdomain = $this->normalizeSubdomainOrFail($kommoService, (string) $data['subdomain']);

        $newToken = trim((string) ($data['access_token'] ?? ''));
        $credentialsChanged = $subdomain !== (string) $kommoAccount->subdomain || $newToken !== '';
        $tokenToUse = $newToken !== '' ? $newToken : (string) $kommoAccount->access_token;

        $attributes = [
            'subdomain' => $subdomain,
        ];

        if ($credentialsChanged) {
            $validation = $this->validateOrFail($kommoService, $subdomain, $tokenToUse);

            $attributes['kommo_account_id'] = $validation['account_id'];
            $attributes['kommo_account_name'] = $validation['account_name'];
            $attributes['last_verified_at'] = Carbon::now();
            $attributes['last_verification_error'] = null;

            if ($newToken !== '') {
                $attributes['access_token'] = $newToken;
            }
        }

        $destination = $this->resolveDestinationOrFail(
            $kommoService,
            $subdomain,
            $tokenToUse,
            (string) $data['pipeline_id'],
            (string) $data['status_id']
        );

        $attributes['pipeline_id'] = $destination['pipeline_id'];
        $attributes['pipeline_name'] = $destination['pipeline_name'];
        $attributes['status_id'] = $destination['status_id'];
        $attributes['status_name'] = $destination['status_name'];

        $kommoAccount->update($attributes);

        return redirect()
            ->route('agencia.kommo.index')
            ->with('success', 'Integração Kommo atualizada com sucesso.');
    }

    public function destroy(Request $request, KommoAccount $kommoAccount): RedirectResponse
    {
        $this->ensureOwnership($request->user()->id, $kommoAccount);
        $kommoAccount->delete();

        return redirect()
            ->route('agencia.kommo.index')
            ->with('success', 'Integração Kommo removida com sucesso.');
    }

    private function resolveOwnedCliente(int $userId, int $clienteId): Cliente
    {
        $cliente = Cliente::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->find($clienteId);

        if (!$cliente) {
            throw ValidationException::withMessages([
                'cliente_id' => ['Selecione um cliente válido da agência.'],
            ]);
        }

        return $cliente;
    }

    private function normalizeSubdomainOrFail(KommoService $kommoService, string $subdomain): string
    {
        $normalized = $kommoService->normalizeSubdomain($subdomain);

        if ($normalized === '' || !preg_match('/^[a-z0-9-]+$/', $normalized)) {
            throw ValidationException::withMessages([
                'subdomain' => ['Informe um subdomínio Kommo válido.'],
            ]);
        }

        return $normalized;
    }

    private function normalizeAndEnsureUniqueName(string $name, int $userId, ?int $ignoreId = null): string
    {
        $normalized = Str::slug($name);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'name' => ['Informe um nome técnico válido para a integração Kommo.'],
            ]);
        }

        $exists = KommoAccount::query()
            ->where('user_id', $userId)
            ->where('name', $normalized)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['Este nome de integração Kommo já está em uso na sua agência.'],
            ]);
        }

        return $normalized;
    }

    private function validateOrFail(KommoService $kommoService, string $subdomain, string $token): array
    {
        $result = $kommoService->validateAccount($subdomain, $token);

        if (!($result['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'access_token' => [$result['message'] ?? 'Não foi possível validar a conta Kommo.'],
            ]);
        }

        return $result;
    }

    private function resolveDestinationOrFail(
        KommoService $kommoService,
        string $subdomain,
        string $token,
        string $pipelineId,
        string $statusId
    ): array {
        $pipelines = $kommoService->listPipelines($subdomain, $token);

        if (!($pipelines['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'pipeline_id' => [$pipelines['message'] ?? 'Não foi possível carregar as pipelines do Kommo.'],
            ]);
        }

        $pipeline = collect($pipelines['pipelines'] ?? [])->firstWhere('id', trim($pipelineId));

        if (!$pipeline) {
            throw ValidationException::withMessages([
                'pipeline_id' => ['A pipeline selecionada não existe mais no Kommo. Escolha uma opção válida.'],
            ]);
        }

        $statuses = $kommoService->listStatuses($subdomain, $token, (string) $pipeline['id']);

        if (!($statuses['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'status_id' => [$statuses['message'] ?? 'Não foi possível carregar os estágios do Kommo.'],
            ]);
        }

        $status = collect($statuses['statuses'] ?? [])->firstWhere('id', trim($statusId));

        if (!$status) {
            throw ValidationException::withMessages([
                'status_id' => ['O estágio selecionado não existe mais na pipeline Kommo escolhida.'],
            ]);
        }

        return [
            'pipeline_id' => (string) $pipeline['id'],
            'pipeline_name' => trim((string) ($pipeline['name'] ?? '')),
            'status_id' => (string) $status['id'],
            'status_name' => trim((string) ($status['name'] ?? '')),
        ];
    }

    private function resolveContextAccount(int $userId, mixed $accountId): ?KommoAccount
    {
        if (empty($accountId)) {
            return null;
        }

        $account = KommoAccount::query()->findOrFail((int) $accountId);
        $this->ensureOwnership($userId, $account);

        return $account;
    }

    private function resolveRequestToken(string $providedToken, ?KommoAccount $account): string
    {
        $token = trim($providedToken);

        if ($token === '' && $account) {
            return (string) $account->access_token;
        }

        return $token;
    }

    private function ensureImmutableFields(KommoAccount $kommoAccount, int $clienteId, string $name): void
    {
        $errors = [];
        $normalized = Str::slug($name);

        if ($normalized !== (string) $kommoAccount->name) {
            $errors['name'] = ['O nome da integração Kommo não pode ser alterado depois da criação.'];
        }

        if ((int) $kommoAccount->cliente_id !== $clienteId) {
            $errors['cliente_id'] = ['O cliente da integração Kommo não pode ser alterado depois da criação.'];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function ensureOwnership(int $userId, KommoAccount $kommoAccount): void
    {
        abort_unless((int) $kommoAccount->user_id === $userId, 403);
    }
}
