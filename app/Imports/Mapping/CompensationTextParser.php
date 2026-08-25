<?php

namespace App\Imports\Mapping;

use App\Enums\CompensationPeriod;

class CompensationTextParser
{
    /** @return array{min: ?int, max: ?int, currency: ?string, period: ?string, warnings: list<string>} */
    public function parse(mixed $value): array
    {
        if ($value === null || trim((string) $value) === '') {
            return ['min' => null, 'max' => null, 'currency' => null, 'period' => null, 'warnings' => []];
        }

        $text = trim((string) $value);
        preg_match_all('/\d[\d.,]*/', $text, $matches);
        $amounts = array_values(array_filter(array_map($this->amount(...), $matches[0] ?? []), fn (?int $amount): bool => $amount !== null));
        $warnings = [];

        if ($amounts === [] || count($amounts) > 2) {
            $warnings[] = "Compensatiewaarde [{$text}] bevat geen eenduidig bedrag of bereik.";
            $amounts = [];
        }

        $currency = match (true) {
            str_contains($text, '€'), preg_match('/\bEUR\b/i', $text) === 1 => 'EUR',
            str_contains($text, '$'), preg_match('/\bUSD\b/i', $text) === 1 => 'USD',
            str_contains($text, '£'), preg_match('/\bGBP\b/i', $text) === 1 => 'GBP',
            default => null,
        };
        if ($currency === null) {
            $warnings[] = "Compensatiewaarde [{$text}] heeft geen herkenbare valuta.";
        }

        $period = $this->period($text);
        if ($period === null) {
            $warnings[] = "Compensatiewaarde [{$text}] heeft geen eenduidige periode.";
        }

        return [
            'min' => $amounts[0] ?? null,
            'max' => $amounts[1] ?? null,
            'currency' => $currency,
            'period' => $period,
            'warnings' => $warnings,
        ];
    }

    private function amount(string $value): ?int
    {
        $digits = preg_replace('/\D/', '', $value);

        return $digits === '' ? null : (int) $digits;
    }

    private function period(string $value): ?string
    {
        $periods = [
            CompensationPeriod::Hour->value => ['hour', 'hourly', 'uur'],
            CompensationPeriod::Day->value => ['day', 'daily', 'dag'],
            CompensationPeriod::Week->value => ['week', 'weekly'],
            CompensationPeriod::Month->value => ['month', 'monthly', 'maand'],
            CompensationPeriod::Year->value => ['year', 'yearly', 'annual', 'annually', 'jaar'],
        ];

        foreach ($periods as $period => $terms) {
            foreach ($terms as $term) {
                if (preg_match('/\b'.preg_quote($term, '/').'\b/iu', $value) === 1) {
                    return $period;
                }
            }
        }

        return null;
    }
}
