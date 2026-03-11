<?php

use Illuminate\Support\Facades\Schedule;

return Schedule::command('grupo-conjunto-mensagens:dispatch')
    ->everyMinute()
    ->withoutOverlapping(55)
    ->onOneServer();
