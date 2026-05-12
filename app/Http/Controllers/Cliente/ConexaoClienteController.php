<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\Conexao;
use App\Models\Iamodelo;
use App\Services\UazapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ConexaoClienteController extends Controller
{
    public function __construct(protected UazapiService $uazapiService)
    {
    }

    public function index(Request $request)
    {
        $clienteId = $request->user('client')->id;

        $conexoes = Conexao::with(['whatsappApi', 'credential', 'iamodelo', 'assistant'])
            ->where('cliente_id', $clienteId)
            ->latest()
            ->get();

        $assistants = Assistant::query()
            ->where('cliente_id', $clienteId)
            ->orderBy('name')
            ->get();

        $iamodelos = Iamodelo::query()
            ->orderBy('nome')
            ->get();

        return view('cliente.conexoes.index', compact('conexoes', 'assistants', 'iamodelos'));
    }

    public function update(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($conexao);
        $clienteId = $request->user('client')->id;

        if (!$conexao->permitiredicao) {
            abort(403);
        }

        $conexao->loadMissing('credential');

        $credentialPlatformId = $conexao->credential?->iaplataforma_id;

        $data = $request->validate([
            'assistant_id' => [
                'required',
                Rule::exists('assistants', 'id')->where('cliente_id', $clienteId),
            ],
            'model' => [
                'required',
                Rule::exists('iamodelos', 'id'),
                function (string $attribute, mixed $value, \Closure $fail) use ($credentialPlatformId): void {
                    if (!$credentialPlatformId) {
                        return;
                    }

                    $isCompatible = Iamodelo::query()
                        ->whereKey($value)
                        ->where('iaplataforma_id', $credentialPlatformId)
                        ->exists();

                    if (!$isCompatible) {
                        $fail('O modelo selecionado nao e compativel com a credencial desta conexao.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $conexao->assistant_id = (int) $data['assistant_id'];
        $conexao->model = (int) $data['model'];
        $conexao->is_active = $request->has('is_active')
            ? $request->boolean('is_active')
            : (bool) $conexao->is_active;
        $conexao->save();

        return redirect()
            ->route('cliente.conexoes.index')
            ->with('success', 'Conexao atualizada com sucesso.');
    }

    public function status(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($conexao);

        $conexao->loadMissing('whatsappApi');
        $defaultStatus = $conexao->status ?? 'pendente';
        if (!$conexao->is_active) {
            return response()->json([
                'status' => $defaultStatus,
                'is_active' => false,
            ]);
        }

        if ($conexao->whatsappApi?->slug !== 'uazapi' || !$conexao->whatsapp_api_key) {
            return response()->json([
                'status' => $defaultStatus,
                'is_active' => true,
            ]);
        }

        $result = $this->uazapiService->instance_status($conexao->whatsapp_api_key);

        if (!empty($result['error'])) {
            Log::warning('Falha ao obter status uazapi (cliente)', [
                'conexao_id' => $conexao->id,
                'response' => $result,
            ]);

            return response()->json([
                'status' => 'erro',
                'message' => Arr::get($result, 'body.message') ?? 'Erro ao consultar a Uazapi.',
            ], 502);
        }

        $status = Arr::get($result, 'instance.status')
            ?? Arr::get($result, 'status')
            ?? Arr::get($result, 'data.status')
            ?? $defaultStatus;

        if (is_string($status)) {
            $status = trim($status);
        }

        if ($status && $conexao->status !== $status) {
            $conexao->status = $status;
            $conexao->save();
        }

        return response()->json([
            'status' => $status,
            'is_active' => true,
        ]);
    }

    public function connect(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($conexao);

        if (!$conexao->is_active) {
            return response()->json([
                'error' => true,
                'message' => 'Conexão inativa. Ative a conexão para conectar o WhatsApp.',
            ], 422);
        }

        $conexao->loadMissing('whatsappApi');
        if ($conexao->whatsappApi?->slug !== 'uazapi' || !$conexao->whatsapp_api_key) {
            return response()->json([
                'error' => true,
                'message' => 'Integração não suporta conexão automática.',
            ], 422);
        }

        $result = $this->uazapiService->instance_connect($conexao->whatsapp_api_key, $conexao->phone);

        if (!empty($result['error'])) {
            return response()->json([
                'error' => true,
                'message' => Arr::get($result, 'body.message') ?? 'Erro ao conectar.',
            ], 422);
        }

        $instanceData = $result['instance'] ?? [];

        return response()->json([
            'paircode' => $instanceData['paircode'] ?? null,
            'qrcode' => $instanceData['qrcode'] ?? null,
            'message' => $result['response'] ?? 'Conectando...',
        ]);
    }

    private function ensureOwner(Conexao $conexao): void
    {
        if ($conexao->cliente_id !== auth('client')->id()) {
            abort(403);
        }
    }
}
