<?php

namespace App\Services;

class ConversationsService
{
    public function __construct(...$params)
    {
        // Chat functionality removed; this service is a stub.
    }

    public function __call($method, $parameters)
    {
        throw new \RuntimeException('ConversationsService is disabled after Chat removal.');
    }
}
