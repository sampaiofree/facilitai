<?php

namespace App\Http\Controllers\Agencia;

use App\Http\Controllers\Controller;
use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\Credential;
use App\Models\Iamodelo;
use App\Models\WhatsappApi;
use App\Services\EvolutionAPIOficial;
use App\Services\UazapiService;
use App\Services\WebshareService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AgenciaConexaoController extends Controller
{
    public function __construct(
        protected WebshareService $webshareService,
        protected UazapiService $uazapiService,
        protected EvolutionAPIOficial $evolutionAPIOficial
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
        $limit = $user->plan?->max_conexoes ?? 0;
        $used = $user->conexoesCount();

        if ($limit <= 0) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Selecione um plano para liberar novas conexões.');
        }

        if ($used >= $limit) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Limite de conexões do plano atingido.');
        }

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
            'whatsapp_api_id' => ['required', Rule::exists('whatsapp_api', 'id')->where('ativo', true)],
        ]);

        $whatsappApi = WhatsappApi::findOrFail($data['whatsapp_api_id']);

        $conexao = new Conexao();
        $conexao->name = $data['name'];
        $conexao->credential_id = $data['credential_id'];
        $conexao->assistant_id = $data['assistant_id'];
        $conexao->cliente_id = $data['cliente_id'];
        $conexao->model = $data['model'];
        $conexao->whatsapp_api_id = $data['whatsapp_api_id'];

        try {
            if ($whatsappApi->slug === 'uazapi') {
                $uazapiData = $request->validate([
                    'phone' => ['required', 'string', 'regex:/^\d{11,}$/'],
                ]);

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

                $token = $uazapiResponse['token']
                    ?? Arr::get($uazapiResponse, 'body.token')
                    ?? Arr::get($uazapiResponse, 'body.data.token');

                if (!$token) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Resposta inesperada da Uazapi: token não encontrado.');
                }

                $conexao->phone = $uazapiData['phone'];
                $conexao->whatsapp_api_key = $token;
                $this->fillProxyData($conexao);
                $conexao->save();

                return redirect()
                    ->route('agencia.conexoes.index')
                    ->with('success', 'Conexão salva com sucesso.');
            }

            if ($whatsappApi->slug === 'api_oficial') {
                $officialValidator = Validator::make(
                    $request->all(),
                    [
                        'token' => ['required', 'string', 'max:2048'],
                        'businessId' => ['required', 'string', 'max:255'],
                        'number' => ['required', 'string', 'regex:/^\d+$/', 'max:30'],
                    ],
                    [
                        'number.regex' => 'O campo number deve conter apenas números.',
                    ]
                );

                if ($officialValidator->fails()) {
                    return redirect()
                        ->back()
                        ->withErrors($officialValidator)
                        ->withInput($request->except('token'));
                }

                $officialData = $officialValidator->validated();
                $this->fillProxyData($conexao);
                $conexao->status = 'pending';
                $conexao->save();

                $evolutionResponse = $this->evolutionAPIOficial->instance_create([
                    'instanceName' => (string) $conexao->id,
                    'token' => $officialData['token'],
                    'businessId' => $officialData['businessId'],
                    'number' => $officialData['number'],
                    'proxyHost' => $conexao->proxy_ip,
                    'proxyPort' => (string) ($conexao->proxy_port ?? ''),
                    'proxyUsername' => $conexao->proxy_username,
                    'proxyPassword' => $conexao->proxy_password,
                    'webhookUrl' => route('api.evolution-api-oficial'),
                ]);

                if (!empty($evolutionResponse['error'])) {
                    $conexao->delete();
                    $message = Arr::get($evolutionResponse, 'body.message')
                        ?? Arr::get($evolutionResponse, 'body.response.message')
                        ?? 'Não foi possível criar a instância na Evolution API Oficial.';

                    return redirect()
                        ->back()
                        ->withInput($request->except('token'))
                        ->with('error', $message);
                }

                $hash = $evolutionResponse['hash']
                    ?? Arr::get($evolutionResponse, 'body.hash')
                    ?? Arr::get($evolutionResponse, 'body.instance.hash')
                    ?? Arr::get($evolutionResponse, 'body.instance.token');

                if (!$hash) {
                    $conexao->delete();

                    return redirect()
                        ->back()
                        ->withInput($request->except('token'))
                        ->with('error', 'Resposta inesperada da Evolution API Oficial: hash não encontrado.');
                }

                $conexao->whatsapp_api_key = (string) $hash;
                $conexao->status = 'active';
                $conexao->save();

                return redirect()
                    ->route('agencia.conexoes.index')
                    ->with('success', 'Conexão criada com sucesso na API Oficial.');
            }

            return redirect()
                ->back()
                ->withInput($request->except('token'))
                ->with('error', 'Integração WhatsApp ainda não suportada para criação.');
        } catch (\Throwable $exception) {
            Log::error('Falha ao criar conexão da agência.', [
                'user_id' => $user->id,
                'whatsapp_api_id' => $data['whatsapp_api_id'] ?? null,
                'slug' => $whatsappApi->slug ?? null,
                'conexao_id' => $conexao->id ?? null,
                'error' => $exception->getMessage(),
            ]);

            if ($conexao->exists) {
                $conexao->delete();
            }

            return redirect()
                ->back()
                ->withInput($request->except('token'))
                ->with('error', 'Ocorreu um erro ao criar a conexão. Tente novamente.');
        }
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
        ]);

        $conexao->name = $data['name'];
        $conexao->credential_id = $data['credential_id'];
        $conexao->assistant_id = $data['assistant_id'];
        $conexao->cliente_id = $data['cliente_id'];
        $conexao->model = $data['model'];
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

    private function fillProxyData(Conexao $conexao): void
    {
        $proxy = $this->webshareService->getNewProxy();

        $conexao->proxy_ip = $proxy['proxy_address'] ?? null;
        $conexao->proxy_port = isset($proxy['port']) ? (int) $proxy['port'] : null;
        $conexao->proxy_username = $proxy['username'] ?? null;
        $conexao->proxy_password = $proxy['password'] ?? null;
    }
}
