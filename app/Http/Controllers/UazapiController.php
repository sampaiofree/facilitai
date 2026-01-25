<?php

namespace App\Http\Controllers;

use App\Models\UazapiInstance;
use App\Services\UazapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UazapiController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $instances = UazapiInstance::with('assistant')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'status', 'token', 'created_at', 'assistant_id']);

        $assistants = $user->assistants()->get(['id', 'name']);

        return view('uazapi.instances', compact('instances', 'assistants'));
    }

    public function store(Request $request, UazapiService $uazapiService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $result = $uazapiService->instance_init([
            'name' => $validated['name'],
        ]);

        Log::info('Uazapi instance_init response', [
            'user_id' => Auth::id(),
            'payload' => ['name' => $validated['name']],
            'response' => $result,
        ]);

        if (!empty($result['error'])) {
            return redirect()->route('uazapi.instances')
                ->with('error', $this->formatErrorMessage($result['body']));
        }

        $instanceData = $result['instance'] ?? [];
        if (empty($instanceData['id'])) {
            return redirect()->route('uazapi.instances')
                ->with('error', 'Resposta inválida da Uazapi.');
        }

        UazapiInstance::updateOrCreate(
            ['id' => $instanceData['id']],
            [
                'token' => $instanceData['token'] ?? ($result['token'] ?? ''),
                'status' => $instanceData['status'] ?? 'disconnected',
                'name' => $instanceData['name'] ?? $validated['name'],
                'user_id' => Auth::id(),
            ]
        );

        $proxyResult = $uazapiService->configure_proxy($instanceData['token']);
        Log::info('Uazapi configure_proxy response', [
            'user_id' => Auth::id(),
            'instance_id' => $instanceData['id'],
            'response' => $proxyResult,
        ]);

        if (!empty($proxyResult['error'])) {
            return redirect()->route('uazapi.instances')
                ->with('error', 'Instância criada, mas falha ao configurar proxy: ' . $this->formatErrorMessage($proxyResult['body']));
        }

        return redirect()->route('uazapi.instances')
            ->with('success', $result['response'] ?? 'Instância criada com sucesso.');
    }

    public function update(Request $request, UazapiInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('uazapi.instances')
                ->withErrors($validator, 'edit')
                ->with('edit_instance_id', $instance->id)
                ->with('edit_instance_name', $request->input('name'))
                ->with('error', 'Não foi possível atualizar o nome da instância.')
                ->withInput();
        }

        $instance->update([
            'name' => $validator->validated()['name'],
        ]);

        return redirect()->route('uazapi.instances')
            ->with('success', 'Nome da instância atualizado com sucesso.');
    }

    public function connect(Request $request, UazapiInstance $instance, UazapiService $uazapiService)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'connect_mode' => 'required|string|in:qrcode,paircode',
            'phone' => ['nullable', 'string', 'regex:/^\d+$/', 'min:11', 'max:15', 'required_if:connect_mode,paircode'],
        ]);

        $phone = $validated['connect_mode'] === 'paircode'
            ? $validated['phone']
            : null;

        $result = $uazapiService->instance_connect($instance->token, $phone);

        Log::info('Uazapi instance_connect response', [
            'user_id' => Auth::id(),
            'instance_id' => $instance->id,
            'connect_mode' => $validated['connect_mode'],
            'phone' => $phone,
            'response' => $result,
        ]);

        if (!empty($result['error'])) {
            return response()->json([
                'error' => true,
                'message' => $this->formatErrorMessage($result['body']),
            ], 422);
        }

        $instanceData = $result['instance'] ?? [];

        return response()->json([
            'paircode' => $instanceData['paircode'] ?? null,
            'qrcode' => $instanceData['qrcode'] ?? null,
            'message' => $result['response'] ?? 'Conectando...',
        ]);
    }

    public function status(UazapiInstance $instance, UazapiService $uazapiService)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $uazapiService->instance_status($instance->token);

        Log::info('Uazapi instance_status response', [
            'user_id' => Auth::id(),
            'instance_id' => $instance->id,
            'response' => $result,
        ]);

        if (!empty($result['error'])) {
            return response()->json([
                'error' => true,
                'message' => $this->formatErrorMessage($result['body']),
            ], 502);
        }

        $instanceData = $result['instance'] ?? [];
        $status = $instanceData['status'] ?? null;

        if ($status === null) {
            return response()->json([
                'error' => true,
                'message' => 'Status não retornado pela Uazapi.',
            ], 502);
        }

        $proxyResult = $uazapiService->instance_proxy($instance->token);

        Log::info('Uazapi instance_proxy response', [
            'user_id' => Auth::id(),
            'instance_id' => $instance->id,
            'response' => $proxyResult,
        ]);

        $proxyUrl = null;
        if (empty($proxyResult['error'])) {
            $proxyUrl = $proxyResult['proxy_url'] ?? null;
        }

        return response()->json([
            'status' => $status,
            'proxy_url' => $proxyUrl,
        ]);
    }

    public function assignAssistant(Request $request, UazapiInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'assistant_id' => 'required|integer|exists:assistants,id',
        ]);

        $assistant = Auth::user()->assistants()->find($validated['assistant_id']);
        if (!$assistant) {
            return redirect()->route('uazapi.instances')
                ->with('error', 'Assistente inválido para o usuário atual.');
        }

        $instance->update(['assistant_id' => $assistant->id]);

        return redirect()->route('uazapi.instances')
            ->with('success', 'Assistente vinculado com sucesso.');
    }

    public function destroy(Request $request, UazapiInstance $instance, UazapiService $uazapiService)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $disconnectResult = $uazapiService->instance_disconnect($instance->token);

        Log::info('Uazapi instance_disconnect response', [
            'user_id' => Auth::id(),
            'instance_id' => $instance->id,
            'response' => $disconnectResult,
        ]);

        if (!empty($disconnectResult['error'])) {
            return redirect()->route('uazapi.instances')
                ->with('error', 'Falha ao desconectar a instância: ' . $this->formatErrorMessage($disconnectResult['body']));
        }

        $deleteResult = $uazapiService->instance_delete($instance->token);

        Log::info('Uazapi instance_delete response', [
            'user_id' => Auth::id(),
            'instance_id' => $instance->id,
            'response' => $deleteResult,
        ]);

        if (!empty($deleteResult['error'])) {
            return redirect()->route('uazapi.instances')
                ->with('error', 'Falha ao excluir a instância: ' . $this->formatErrorMessage($deleteResult['body']));
        }

        $instance->delete();

        return redirect()->route('uazapi.instances')
            ->with('success', 'Instância desconectada e removida com sucesso.');
    }

    /**
     * Forward a request payload to the Uazapi endpoint defined in the form input.
     */
    public function forward(Request $request, UazapiService $uazapiService)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'method' => 'sometimes|string|in:get,post,put,patch,delete',
            'payload' => 'sometimes|array',
            'query' => 'sometimes|array',
        ]);

        $method = strtoupper($validated['method'] ?? 'POST');

        $result = $uazapiService->request(
            $method,
            $validated['endpoint'],
            $validated['payload'] ?? [],
            $validated['query'] ?? []
        );

        return response()->json($result);
    }

    protected function formatErrorMessage($body): string
    {
        if (is_string($body)) {
            return $body;
        }

        if (is_array($body)) {
            $parts = [];
            array_walk_recursive($body, function ($value, $key) use (&$parts) {
                $parts[] = is_string($value) ? "$key: $value" : "$key: " . json_encode($value);
            });
            return implode(' | ', array_unique($parts));
        }

        return 'Erro inesperado ao processar a solicitação.';
    }
}
