<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Conexao;
use App\Services\UazapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ConexaoClienteController extends Controller
{
    public function __construct(protected UazapiService $uazapiService)
    {
    }

    public function index(Request $request)
    {
        $clienteId = auth('client')->id();

        $conexoes = Conexao::with('whatsappApi')
            ->where('cliente_id', $clienteId)
            ->latest()
            ->get();

        return view('cliente.conexoes.index', compact('conexoes'));
    }

    public function status(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($conexao);

        $conexao->loadMissing('whatsappApi');
        $defaultStatus = $conexao->status ?? 'pendente';
        if ($conexao->whatsappApi?->slug !== 'uazapi' || !$conexao->whatsapp_api_key) {
            return response()->json(['status' => $defaultStatus]);
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

        return response()->json(['status' => $status]);
    }

    public function connect(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($conexao);

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
