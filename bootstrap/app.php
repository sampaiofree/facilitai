<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: base_path('routes/api.php'),
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function () {
        return [
            require __DIR__ . '/../app/Schedules/process_sequences.php',
            require __DIR__ . '/../app/Schedules/process_agenda_reminders.php',
        ];
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->routeIs('cliente.*') || $request->is('cliente') || $request->is('cliente/*')) {
                return route('cliente.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            if (Auth::guard('client')->check()) {
                return route('cliente.dashboard');
            }

            if (Auth::guard('web')->check()) {
                $user = Auth::guard('web')->user();
                if ($user && $user->is_admin) {
                    return route('adm.dashboard');
                }

                return route('agencia.dashboard');
            }

            if ($request->routeIs('cliente.*') || $request->is('cliente') || $request->is('cliente/*')) {
                return route('cliente.login');
            }

            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Arquivo muito grande. Verifique o limite de upload permitido.',
                ], 413);
            }

            return back()->with('error', 'Arquivo muito grande. Verifique o limite de upload permitido.');
        });
    })->create();
