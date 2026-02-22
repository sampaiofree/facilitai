<?php

use Illuminate\Support\Facades\Schedule;

return Schedule::command('scheduled-messages:dispatch')
    ->everyMinute()
    ->withoutOverlapping();

