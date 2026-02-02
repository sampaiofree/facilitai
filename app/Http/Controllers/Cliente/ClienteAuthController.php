<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\AgencySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClienteAuthController extends Controller
{
    public function create(Request $request): View
    {
        $host = $request->getHost();
        $host = preg_replace('/:\\d+$/', '', $host);
        $settings = AgencySetting::where('custom_domain', $host)->first();

        $logoUrl = null;
        if ($settings && $settings->logo_path) {
            $logoUrl = Storage::disk('public')->url($settings->logo_path);
        }

        return view('cliente.login', [
            'logoUrl' => $logoUrl,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = (bool) $request->boolean('remember');
        $credentials['is_active'] = true;

        if (!Auth::guard('client')->attempt($credentials, $remember)) {
            return back()->withErrors([
                'email' => 'Credenciais invalidas.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $cliente = Auth::guard('client')->user();
        if (!$cliente) {
            Auth::guard('client')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Credenciais invalidas.',
            ])->onlyInput('email');
        }

        if ($cliente->trashed()) {
            Auth::guard('client')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Cliente excluido.',
            ])->onlyInput('email');
        }

        return redirect()->intended(route('cliente.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('cliente.login');
    }
}
