<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
