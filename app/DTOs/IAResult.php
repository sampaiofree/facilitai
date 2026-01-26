<?php

namespace App\DTOs;

class IAResult
{
    public bool $ok;
    public ?string $text;
    public string $provider;
    public ?string $error;
    public ?array $raw;

    public function __construct(bool $ok, ?string $text, string $provider, ?string $error = null, ?array $raw = null)
    {
        $this->ok = $ok;
        $this->text = $text;
        $this->provider = $provider;
        $this->error = $error;
        $this->raw = $raw;
    }

    public static function success(string $text, string $provider, ?array $raw = null): self
    {
        return new self(true, $text, $provider, null, $raw);
    }

    public static function error(string $error, string $provider, ?array $raw = null): self
    {
        return new self(false, null, $provider, $error, $raw);
    }
}
