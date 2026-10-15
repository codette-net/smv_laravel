<?php

namespace App\Rules;

use App\Support\Vacancies\VacancyDescription;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MeaningfulVacancyDescription implements ValidationRule
{
    public function __construct(
        private readonly int $minimum = 50,
        private readonly int $maximum = 20_000,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $length = mb_strlen(app(VacancyDescription::class)->plainText(is_string($value) ? $value : ''));

        if ($length < $this->minimum) {
            $fail("Geef een vacaturebeschrijving van minimaal {$this->minimum} zichtbare tekens.");

            return;
        }

        if ($length > $this->maximum) {
            $fail("De vacaturebeschrijving mag maximaal {$this->maximum} zichtbare tekens bevatten.");
        }
    }
}
