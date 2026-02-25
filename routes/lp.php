<?php

use Illuminate\Support\Facades\Route;

Route::get('/lp-1', function () {return view('homepage.lp1');})->name('lp1');
Route::get('/lp-2', function () {return view('homepage.lp2');})->name('lp2');
Route::get('/lp-3', function () {return view('homepage.lp3');})->name('lp3');
Route::get('/lp-4', function () {return view('homepage.lp4');})->name('lp4');

Route::name('lp.')->group(function () {
    Route::get('/lp6/{cidade?}', function (?string $cidade = null) {
        $queryCidade = request()->query('cidade');
        $rawCidade = is_string($queryCidade) ? $queryCidade : $cidade;

        $sanitizeCidade = static function (?string $value): ?string {
            if (!is_string($value)) {
                return null;
            }

            $value = trim(strip_tags($value));
            if ($value === '') {
                return null;
            }

            if (preg_match('/(?:https?:\\/\\/|www\\.|javascript:|<\\/?script|%3c|%3e)/iu', $value)) {
                return null;
            }

            $value = preg_replace('/[\\x00-\\x1F\\x7F]+/u', ' ', $value) ?? $value;
            $value = preg_replace('/[^\\pL\\pN\\s\\-]/u', '', $value) ?? '';
            $value = preg_replace('/\\s+/u', ' ', trim($value)) ?? '';

            if ($value === '') {
                return null;
            }

            if (mb_strlen($value) > 40) {
                $value = trim(mb_substr($value, 0, 40));
            }

            return $value !== '' ? $value : null;
        };

        $cidadeSanitizada = $sanitizeCidade($rawCidade);
        $cidadeExibicao = $cidadeSanitizada ?? 'sua cidade';

        $cidadeCanonical = $cidadeSanitizada;
        $canonicalUrl = $cidadeCanonical
            ? url('/lp6/' . rawurlencode($cidadeCanonical))
            : url('/lp6');

        $whatsAppNumberRaw = (string) config('services.marketing.whatsapp');
        $whatsAppNumber = preg_replace('/\\D/', '', $whatsAppNumberRaw) ?: '';

        $waMessage = "Olá, quero automatizar o atendimento da minha empresa em {$cidadeExibicao}.\n"
            . "Meu segmento é: _____\n"
            . "Recebo em média: _____ mensagens por dia.\n"
            . "Hoje meu atendimento é: ( ) Manual ( ) Equipe ( ) Já uso algum sistema\n"
            . "Quero entender como a IA pode me ajudar.";

        $whatsAppUrl = $whatsAppNumber !== ''
            ? 'https://wa.me/' . $whatsAppNumber . '?text=' . rawurlencode($waMessage)
            : '#';

        return view('lp.lp6', [
            'cidade' => $cidadeExibicao,
            'cidadeRaw' => $rawCidade,
            'cidadeValida' => $cidadeSanitizada !== null,
            'whatsAppUrl' => $whatsAppUrl,
            'whatsAppEnabled' => $whatsAppNumber !== '',
            'canonicalUrl' => $canonicalUrl,
            'metaTitle' => "Atendimento com IA no WhatsApp em {$cidadeExibicao} | Automatize e venda mais",
            'metaDescription' => "Automatize o atendimento no WhatsApp em {$cidadeExibicao} com IA, responda mais rápido e aumente vendas sem ampliar equipe.",
        ]);
    })->name('lp6.city');
});
