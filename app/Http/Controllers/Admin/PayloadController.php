<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayloadController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'api_route' => ['required', 'string'],
            'payload' => ['required', 'string'],
        ]);

        $payload = json_decode($data['payload'], true);
        if ($payload === null && json_last_error() !== JSON_ERROR_NONE) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'JSON inválido. Verifique o payload e tente novamente.');
        }

        $url = rtrim(config('app.url'), '/') . $data['api_route'];

        try {
            // Em ambiente local com certificado autoassinado, desabilita a verificação SSL.
            $client = Http::timeout(30);
            if (app()->environment('local')) {
                $client = $client->withOptions(['verify' => false]);
            }

            $response = $client->post($url, $payload);
        } catch (\Throwable $exception) {
            Log::channel('uazapijob')->error('PayloadController request failed', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Falha ao enviar o payload para a API.');
        }

        if ($response->failed()) {
            return redirect()
                ->back()
                ->with('error', 'API retornou erro: ' . $response->status());
        }

        return redirect()
            ->back()
            ->with('success', 'Payload enviado com sucesso.');
    }
}
