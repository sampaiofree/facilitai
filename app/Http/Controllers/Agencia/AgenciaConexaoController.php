<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\Credential;
use App\Models\Iamodelo;
use App\Models\WhatsappApi;
use App\Services\UazapiService;
use App\Services\WebshareService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AgenciaConexaoController extends Controller
{
    public function __construct(
        protected WebshareService $webshareService,
        protected UazapiService $uazapiService
    )
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $conexoes = Conexao::with(['credential', 'assistant', 'cliente', 'iamodelo', 'whatsappApi'])
            ->whereHas('cliente', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->get();

        return view('agencia.conexoes.index', [
            'conexoes' => $conexoes,
            'credentials' => Credential::where('user_id', $user->id)->orderBy('name')->get(),
            'assistants' => Assistant::where('user_id', $user->id)->orderBy('name')->get(),
            'clientes' => Cliente::where('user_id', $user->id)->orderBy('nome')->get(),
            'iamodelos' => Iamodelo::orderBy('nome')->get(),
            'whatsappApis' => WhatsappApi::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'credential_id' => [
                'required',
                Rule::exists('credentials', 'id')->where('user_id', $user->id),
            ],
            'assistant_id' => [
                'required',
                Rule::exists('assistants', 'id')->where('user_id', $user->id),
            ],
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where('user_id', $user->id),
            ],
            'model' => ['required', 'exists:iamodelos,id'],
            'whatsapp_api_id' => ['required', 'exists:whatsapp_api,id'],
            'phone' => ['required', 'string', 'regex:/^\d{11,}$/'],
        ]);

        $whatsappApi = WhatsappApi::findOrFail($data['whatsapp_api_id']);

        $conexao = new Conexao();
        $conexao->name = $data['name'];
        $conexao->credential_id = $data['credential_id'];
        $conexao->assistant_id = $data['assistant_id'];
        $conexao->cliente_id = $data['cliente_id'];
        $conexao->model = $data['model'];
        $conexao->whatsapp_api_id = $data['whatsapp_api_id'];
        $conexao->phone = $data['phone'];

        if ($whatsappApi->slug === 'uazapi') {
            $initPayload = [
                'name' => $data['name'],
                'systemName' => 'FacilitAI ' . $data['name'],
            ];
            $uazapiResponse = $this->uazapiService->instance_init($initPayload);

            if (!empty($uazapiResponse['error'])) {
                $message = Arr::get($uazapiResponse['body'], 'message', 'Não foi possível criar a instância uazapi.');
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', $message);
            }

            $token = $uazapiResponse['token'] ?? Arr::get($uazapiResponse, 'body.token') ?? Arr::get($uazapiResponse, 'body.data.token');

            if (!$token) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Resposta inesperada da Uazapi: token não encontrado.');
            }

            $conexao->whatsapp_api_key = $token;
        }
        $proxy = $this->webshareService->getNewProxy();
        $conexao->proxy_ip = $proxy['proxy_address'] ?? null;
        $conexao->proxy_port = isset($proxy['port']) ? (int) $proxy['port'] : null;
        $conexao->proxy_username = $proxy['username'] ?? null;
        $conexao->proxy_password = $proxy['password'] ?? null;
        $conexao->save();

        return redirect()
            ->route('agencia.conexoes.index')
            ->with('success', 'Conexão salva com sucesso.');
    }

    public function update(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($request, $conexao);

        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'credential_id' => [
                'required',
                Rule::exists('credentials', 'id')->where('user_id', $user->id),
            ],
            'assistant_id' => [
                'required',
                Rule::exists('assistants', 'id')->where('user_id', $user->id),
            ],
            'cliente_id' => [
                'required',
                Rule::exists('clientes', 'id')->where('user_id', $user->id),
            ],
            'model' => ['required', 'exists:iamodelos,id'],
            'phone' => ['required', 'string', 'regex:/^\d{11,}$/'],
        ]);

        $conexao->name = $data['name'];
        $conexao->credential_id = $data['credential_id'];
        $conexao->assistant_id = $data['assistant_id'];
        $conexao->cliente_id = $data['cliente_id'];
        $conexao->model = $data['model'];
        $conexao->phone = $data['phone'];
        $conexao->save();

        return redirect()
            ->route('agencia.conexoes.index')
            ->with('success', 'Conexão atualizada com sucesso.');
    }

    public function status(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($request, $conexao);

        $conexao->loadMissing('whatsappApi');
        $defaultStatus = $conexao->status ?? 'pendente';
        if ($conexao->whatsappApi?->slug !== 'uazapi' || !$conexao->whatsapp_api_key) {
            return response()->json(['status' => $defaultStatus]);
        }

        $result = $this->uazapiService->instance_status($conexao->whatsapp_api_key);

        if (!empty($result['error'])) {
            Log::warning('Falha ao obter status uazapi', [
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
        $this->ensureOwner($request, $conexao);

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

    public function destroy(Request $request, Conexao $conexao)
    {
        $this->ensureOwner($request, $conexao);

        $conexao->loadMissing('whatsappApi');
        if ($conexao->whatsappApi?->slug === 'uazapi' && $conexao->whatsapp_api_key) {
            try {
                $this->uazapiService->instance_disconnect($conexao->whatsapp_api_key);
            } catch (\Throwable $exception) {
                Log::error('Falha ao desconectar instância uazapi antes de excluir a conexão.', [
                    'conexao_id' => $conexao->id,
                    'exception' => $exception->getMessage(),
                ]);
            }

            try {
                $this->uazapiService->instance_delete($conexao->whatsapp_api_key);
            } catch (\Throwable $exception) {
                Log::error('Falha ao excluir instância uazapi após desconectar.', [
                    'conexao_id' => $conexao->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $conexao->delete();

        return redirect()
            ->route('agencia.conexoes.index')
            ->with('success', 'Conexão removida com sucesso.');
    }

    private function ensureOwner(Request $request, Conexao $conexao): void
    {
        $conexao->loadMissing('cliente');

        if ($conexao->cliente?->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
