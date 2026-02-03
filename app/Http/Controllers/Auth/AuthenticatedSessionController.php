<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\HotmarlWebhook;
use App\Models\AgencySetting;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $host = request()->getHost();
        $appUrlHost = parse_url(config('app.url'), PHP_URL_HOST);
        $domainAllowed = true;

        if ($appUrlHost && strcasecmp($host, $appUrlHost) !== 0) {
            $domainAllowed = AgencySetting::where('custom_domain', $host)->exists();
        }

        return view('auth.login', [
            'domainAllowed' => $domainAllowed,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        // Verifica se o usuário é admin ou tem uma compra registrada
        /*if (!$user->is_admin && !$user->hotmartWebhooks()) {
            Auth::logout(); // Desloga o usuário

            // Retorna para a tela de login com mensagem de erro
            return back()->withErrors([
                'email' => 'Você precisa ter uma assinatura ativa para acessar o sistema.',
            ]);
        }*/

        $targetPath = $user->is_admin
            ? route('adm.dashboard', absolute: false)
            : route('agencia.dashboard', absolute: false);

        return redirect()->intended($targetPath);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
