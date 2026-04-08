<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ClientePasswordController extends Controller
{
    public function edit(): View
    {
        return view('cliente.password.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:client'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $cliente = $request->user('client');

        $cliente->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('cliente.password.edit')
            ->with('success', 'Senha atualizada com sucesso.');
    }
}
