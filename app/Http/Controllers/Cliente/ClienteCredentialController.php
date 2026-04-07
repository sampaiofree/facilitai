<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteCredentialController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $cliente = auth('client')->user();
        $credentials = $cliente->credentials()
            ->with('iaplataforma')
            ->latest()
            ->get();

        if ($credentials->isEmpty()) {
            return redirect()
                ->route('cliente.dashboard')
                ->with('error', 'Nenhuma credencial vinculada à sua conta.');
        }

        return view('cliente.credentials.index', compact('credentials'));
    }

    public function update(Request $request, Credential $credential): RedirectResponse
    {
        $this->ensureOwner($credential);

        $data = $request->validate([
            'editing_id' => ['nullable', 'integer'],
            'token' => ['required', 'string'],
        ]);

        $credential->token = $data['token'];
        $credential->save();

        return redirect()
            ->route('cliente.credentials.index')
            ->with('success', 'Token atualizado com sucesso.');
    }

    private function ensureOwner(Credential $credential): void
    {
        if ((int) ($credential->cliente_id ?? 0) !== (int) auth('client')->id()) {
            abort(403);
        }
    }
}
