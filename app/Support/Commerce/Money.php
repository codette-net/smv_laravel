<?php

namespace App\Support\Commerce;

final class Money
{
    public static function format(int $minorUnits, string $currency = 'EUR'): string
    {
        $currency = strtoupper($currency);
        $amount = number_format($minorUnits / 100, 2, ',', '.');

        return $currency === 'EUR' ? '€ '.$amount : $currency.' '.$amount;
    }
}
