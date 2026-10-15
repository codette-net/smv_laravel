<?php

use App\Enums\CompensationPeriod;
use App\Enums\SalaryBasis;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\Vacancy;
use App\Support\Seo\StructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function compensationVacancy(array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'salary_min' => null,
        'salary_max' => null,
        'salary_currency' => null,
        'salary_period' => null,
        'salary_basis' => null,
        'rate_min' => null,
        'rate_max' => null,
        'rate_currency' => null,
        'rate_period' => null,
        ...$attributes,
    ]);
}

test('only explicit valid EUR monthly gross FTE salary is comparable', function () {
    $comparable = collect([
        ['salary_min' => 3500],
        ['salary_max' => 5000],
        ['salary_min' => 4000, 'salary_max' => 4000],
        ['salary_min' => 3500, 'salary_max' => 5000],
    ])->map(fn (array $range): Vacancy => compensationVacancy([
        ...$range,
        'salary_currency' => 'eur',
        'salary_period' => CompensationPeriod::Month,
        'salary_basis' => SalaryBasis::GrossFullTimeEquivalent,
        'status' => VacancyStatus::Draft,
    ]));

    $incomparable = collect([
        ['salary_min' => 3500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossOfferedHours],
        ['salary_min' => 3500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::Unknown],
        ['salary_min' => 3500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => null],
        ['salary_min' => 3500, 'salary_currency' => 'USD', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
        ['salary_min' => 3500, 'salary_currency' => null, 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
        ['salary_min' => 3500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Year, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
        ['salary_min' => 3500, 'salary_currency' => 'EUR', 'salary_period' => null, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
        ['salary_min' => 0, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
        ['salary_min' => -1, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
        ['salary_min' => 5000, 'salary_max' => 3500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
        ['salary_min' => 3500, 'salary_max' => 0, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent],
    ])->map(fn (array $attributes): Vacancy => compensationVacancy($attributes));

    expect(Vacancy::withComparableMonthlySalary()->pluck('id')->all())
        ->toEqualCanonicalizing($comparable->pluck('id')->all());

    $comparable->each(fn (Vacancy $vacancy) => expect($vacancy->fresh()->hasComparableMonthlySalary())->toBeTrue());
    $incomparable->each(fn (Vacancy $vacancy) => expect($vacancy->fresh()->hasComparableMonthlySalary())->toBeFalse());
});

test('only explicit valid EUR hourly rates are comparable', function () {
    $comparable = collect([
        ['rate_min' => 85],
        ['rate_max' => 125],
        ['rate_min' => 100, 'rate_max' => 100],
        ['rate_min' => 85, 'rate_max' => 125],
    ])->map(fn (array $range): Vacancy => compensationVacancy([
        ...$range,
        'rate_currency' => 'eur',
        'rate_period' => CompensationPeriod::Hour,
    ]));

    $incomparable = collect([
        ['rate_min' => 85, 'rate_currency' => 'USD', 'rate_period' => CompensationPeriod::Hour],
        ['rate_min' => 85, 'rate_currency' => null, 'rate_period' => CompensationPeriod::Hour],
        ['rate_min' => 85, 'rate_currency' => 'EUR', 'rate_period' => CompensationPeriod::Day],
        ['rate_min' => 85, 'rate_currency' => 'EUR', 'rate_period' => null],
        ['rate_min' => 0, 'rate_currency' => 'EUR', 'rate_period' => CompensationPeriod::Hour],
        ['rate_min' => -1, 'rate_currency' => 'EUR', 'rate_period' => CompensationPeriod::Hour],
        ['rate_min' => 125, 'rate_max' => 85, 'rate_currency' => 'EUR', 'rate_period' => CompensationPeriod::Hour],
    ])->map(fn (array $attributes): Vacancy => compensationVacancy($attributes));

    expect(Vacancy::withComparableHourlyRate()->pluck('id')->all())
        ->toEqualCanonicalizing($comparable->pluck('id')->all());

    $comparable->each(fn (Vacancy $vacancy) => expect($vacancy->fresh()->hasComparableHourlyRate())->toBeTrue());
    $incomparable->each(fn (Vacancy $vacancy) => expect($vacancy->fresh()->hasComparableHourlyRate())->toBeFalse());
});

test('existing compensation remains intact with unknown basis', function () {
    $vacancy = compensationVacancy([
        'salary_min' => 3200,
        'salary_max' => 4100,
        'salary_currency' => 'EUR',
        'salary_period' => CompensationPeriod::Month,
    ])->fresh();

    expect($vacancy->salary_min)->toBe(3200)
        ->and($vacancy->salary_max)->toBe(4100)
        ->and($vacancy->salary_currency)->toBe('EUR')
        ->and($vacancy->salary_period)->toBe(CompensationPeriod::Month)
        ->and($vacancy->salary_basis)->toBeNull()
        ->and($vacancy->hasComparableMonthlySalary())->toBeFalse();
});

test('public labels distinguish salary basis without calling unknown salary FTE', function () {
    $fte = compensationVacancy(['salary_min' => 3500, 'salary_max' => 4500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossFullTimeEquivalent]);
    $offered = compensationVacancy(['salary_min' => 3500, 'salary_max' => 4500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossOfferedHours]);
    $unknown = compensationVacancy(['salary_min' => 3500, 'salary_max' => 4500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => null]);

    expect($fte->compensationLabel())->toContain('bruto per maand (o.b.v. fulltime)')
        ->and($offered->compensationLabel())->toContain('bruto per maand (voor aangeboden uren)')
        ->and($unknown->compensationLabel())->toContain('per maand')->not->toContain('fulltime', 'aangeboden uren');
});

test('JobPosting omits invalid legacy salary and preserves valid basis-neutral output', function () {
    $invalid = compensationVacancy(['salary_min' => 0, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month]);
    $offered = compensationVacancy(['salary_min' => 3500, 'salary_currency' => 'EUR', 'salary_period' => CompensationPeriod::Month, 'salary_basis' => SalaryBasis::GrossOfferedHours]);

    expect(StructuredData::jobPosting($invalid))->not->toHaveKey('baseSalary')
        ->and(StructuredData::jobPosting($offered)['baseSalary']['currency'])->toBe('EUR')
        ->and(StructuredData::jobPosting($offered)['baseSalary']['value']['unitText'])->toBe('MONTH');
});
