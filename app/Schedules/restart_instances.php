<?php

use Illuminate\Support\Facades\Schedule;

return Schedule::command('instances:restart-active')
    ->dailyAt('05:00')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
