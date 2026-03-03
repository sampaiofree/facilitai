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

        $digits = $this->normalizeBrazilianMobileDigits($digits);

        if (!preg_match('/^\d{11,15}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * Retorna candidatos para lookup de lead por telefone.
     * Inclui variações BR com/sem nono dígito para evitar duplicidade de cadastro.
     *
     * @return array<int, string>
     */
    public function buildLeadPhoneLookupCandidates(?string $value, string $defaultCountryCode = '55'): array
    {
        $normalized = $this->normalizeLeadPhone($value, $defaultCountryCode);
        if ($normalized === null) {
            return [];
        }

        $candidates = [$normalized];

        $withoutNinth = $this->removeBrazilianMobileNinthDigit($normalized);
        if ($withoutNinth !== null) {
            $candidates[] = $withoutNinth;
        }

        $withNinth = $this->addBrazilianMobileNinthDigit($normalized);
        if ($withNinth !== null) {
            $candidates[] = $withNinth;
        }

        return array_values(array_unique($candidates));
    }

    private function normalizeBrazilianMobileDigits(string $digits): string
    {
        return $this->addBrazilianMobileNinthDigit($digits) ?? $digits;
    }

    private function addBrazilianMobileNinthDigit(string $digits): ?string
    {
        if (!preg_match('/^55(\d{2})([6-9]\d{7})$/', $digits, $matches)) {
            return null;
        }

        return '55' . $matches[1] . '9' . $matches[2];
    }

    private function removeBrazilianMobileNinthDigit(string $digits): ?string
    {
        if (!preg_match('/^55(\d{2})9([6-9]\d{7})$/', $digits, $matches)) {
            return null;
        }

        return '55' . $matches[1] . $matches[2];
    }
}
