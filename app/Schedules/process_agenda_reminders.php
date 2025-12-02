<?php

use Illuminate\Support\Facades\Schedule;

return Schedule::command('agenda:process-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping();
