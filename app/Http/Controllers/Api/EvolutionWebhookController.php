<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class EvolutionWebhookController extends Controller
{
    public function __call($method, $parameters)
    {
        abort(410, 'Chat webhook removed.');
    }
}
