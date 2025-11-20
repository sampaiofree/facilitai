<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário está logado E se ele é um administrador.
        if (Auth::check() && Auth::user()->is_admin) {
            // Se for, permite que a requisição continue para o controller.
            return $next($request);
        }

        // Se não for, redireciona para o dashboard com uma mensagem de erro.
        return redirect('/dashboard')->with('error', 'Acesso negado. Você não tem permissão para acessar esta área.');
    }
}