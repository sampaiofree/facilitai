<?php

use Illuminate\Support\Facades\Schedule;

return Schedule::command('scheduled-messages:prune --days=180')
    ->monthlyOn(1, '03:30')
    ->withoutOverlapping();

