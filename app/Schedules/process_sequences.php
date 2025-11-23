<?php

use Illuminate\Support\Facades\Schedule;

return Schedule::command('sequences:process')
    ->everyMinute()
    ->withoutOverlapping();
