<?php

namespace App\Http\Controllers;

class ChatController extends Controller
{
    public function __call($method, $parameters)
    {
        abort(410, 'Chat functionality removed.');
    }
}
