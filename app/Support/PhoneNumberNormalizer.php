<?php

namespace App\Support;

class PhoneNumberNormalizer
{
    public function normalizeLeadPhone(?string $value, string $defaultCountryCode = '55'): ?string
    {
        if ($value === null) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        $hasPlusPrefix = str_starts_with($raw, '+');
        $hasInternationalPrefix = str_starts_with($raw, '00');

        $digits = preg_replace('/\D+/', '', $raw);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if ($hasInternationalPrefix && str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if ($digits === '') {
            return null;
        }

        if (!$hasPlusPrefix && !$hasInternationalPrefix && in_array(strlen($digits), [10, 11], true)) {
            $countryCode = preg_replace('/\D+/', '', $defaultCountryCode) ?? '';
            if ($countryCode !== '') {
                $digits = $countryCode . $digits;
            }
        }

        if (!preg_match('/^\d{11,15}$/', $digits)) {
            return null;
        }

        return $digits;
    }
}
