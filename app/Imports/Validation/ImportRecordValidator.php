<?php

namespace App\Imports\Validation;

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\CompensationPeriod;
use App\Enums\SalaryBasis;
use App\Imports\Mapping\NormalizedVacancyData;
use App\Models\ImportSource;

class ImportRecordValidator
{
    public function __construct(private readonly TaxonomyResolver $taxonomy, private readonly EmployerResolver $employers) {}

    public function validate(NormalizedVacancyData $data, ImportSource $source): ImportRecordOutcome
    {
        $values = $data->toArray();
        $tags = collect((array) data_get($values, 'tags', []))
            ->filter(fn (mixed $tag): bool => is_scalar($tag) && trim((string) $tag) !== '')
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->values()
            ->all();
        data_set($values, 'tags', $tags);
        $data = new NormalizedVacancyData($values);
        $errors = [];
        $warnings = [];
        $unresolved = [];
        $resolved = [];
        $reference = $data->get('source_reference');
        $title = $data->get('vacancy.title');
        if (! is_scalar($reference) || trim((string) $reference) === '' || mb_strlen((string) $reference) > 255) {
            $errors[] = ['code' => 'source_reference_invalid', 'field' => 'source_reference', 'message' => 'Bronreferentie ontbreekt of is ongeldig.'];
        }
        if (! is_string($title) || trim($title) === '' || mb_strlen($title) > 255) {
            $errors[] = ['code' => 'title_invalid', 'field' => 'vacancy.title', 'message' => 'Vacaturetitel ontbreekt of is ongeldig.'];
        }
        foreach (['vacancy.advertised_country', 'vacancy.workplace_country'] as $countryField) {
            $country = data_get($values, $countryField);
            if (blank($country)) {
                continue;
            }

            $country = trim((string) $country);
            data_set($values, $countryField, $country);
            if (preg_match('/^[A-Z]{2}$/', $country) !== 1) {
                $errors[] = ['code' => 'country_invalid', 'field' => $countryField, 'message' => 'Landcode moet een ISO 3166-1 alpha-2 code in hoofdletters zijn.'];
            }
        }
        foreach (['vacancy.source_requisition_id', 'vacancy.advertised_state', 'vacancy.advertised_postal_code', 'vacancy.workplace_city', 'vacancy.source_language', 'vacancy.source_product_code'] as $metadataField) {
            $value = data_get($values, $metadataField);
            if ($value !== null && (! is_scalar($value) || mb_strlen(trim((string) $value)) > 255)) {
                $errors[] = ['code' => 'metadata_invalid', 'field' => $metadataField, 'message' => 'Bronmetadata is ongeldig of te lang.'];
            } elseif (is_scalar($value)) {
                data_set($values, $metadataField, trim((string) $value));
            }
        }
        $employer = $this->employers->resolve($source, $data);
        if ($employer['unresolved'] ?? false) {
            $unresolved[] = ['code' => $employer['code'], 'field' => 'employer', 'message' => $employer['message']];
        }
        $configuredMode = $data->get('vacancy.application_mode');
        $mode = $configuredMode === null ? null : ApplicationMode::tryFrom((string) $configuredMode);
        if ($configuredMode !== null && ! $mode) {
            $errors[] = ['code' => 'application_mode_invalid', 'field' => 'vacancy.application_mode', 'message' => 'Sollicitatiemodus is ongeldig.'];
        } elseif ($mode === ApplicationMode::External && ! filter_var($data->get('vacancy.application_url'), FILTER_VALIDATE_URL)) {
            $errors[] = ['code' => 'application_url_invalid', 'field' => 'vacancy.application_url', 'message' => 'Een geldige sollicitatielink is vereist.'];
        } elseif ($mode === ApplicationMode::Email && ! filter_var($data->get('vacancy.application_email'), FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['code' => 'application_email_invalid', 'field' => 'vacancy.application_email', 'message' => 'Een geldig sollicitatie-e-mailadres is vereist.'];
        }
        foreach (['salary', 'rate'] as $kind) {
            foreach (['min', 'max'] as $endpoint) {
                $field = "vacancy.{$kind}_{$endpoint}";
                $amount = data_get($values, $field);
                if ($amount === null || $amount === '') {
                    data_set($values, $field, null);

                    continue;
                }

                $normalizedAmount = filter_var($amount, FILTER_VALIDATE_INT);
                if ($normalizedAmount === false || $normalizedAmount < 0) {
                    $errors[] = ['code' => "{$kind}_invalid", 'field' => $field, 'message' => 'Compensatiebedragen moeten positieve gehele bedragen zijn.'];

                    continue;
                }
                if ($normalizedAmount === 0) {
                    data_set($values, $field, null);
                    $warnings[] = ['code' => "{$kind}_zero_ignored", 'field' => $field, 'message' => 'Een nulbedrag is als onbekend behandeld en niet als vergelijkbare compensatie opgeslagen.'];

                    continue;
                }

                data_set($values, $field, $normalizedAmount);
            }

            $min = data_get($values, "vacancy.{$kind}_min");
            $max = data_get($values, "vacancy.{$kind}_max");
            if (is_int($min) && is_int($max) && $min > $max) {
                $errors[] = ['code' => "{$kind}_invalid", 'field' => "vacancy.{$kind}", 'message' => 'Het minimumbedrag mag niet hoger zijn dan het maximumbedrag.'];
            }

            $currencyField = "vacancy.{$kind}_currency";
            $currency = data_get($values, $currencyField);
            if (filled($currency)) {
                $normalizedCurrency = match (mb_strtolower(trim((string) $currency))) {
                    '€', 'eur', 'euro' => 'EUR',
                    '$', 'usd' => 'USD',
                    '£', 'gbp' => 'GBP',
                    default => strtoupper(trim((string) $currency)),
                };
                if (preg_match('/^[A-Z]{3}$/', $normalizedCurrency) !== 1) {
                    data_set($values, $currencyField, null);
                    $warnings[] = ['code' => "{$kind}_currency_unresolved", 'field' => $currencyField, 'message' => 'Compensatievaluta is onbekend en daarom niet vergelijkbaar.'];
                } else {
                    data_set($values, $currencyField, $normalizedCurrency);
                }
            }

            $periodField = "vacancy.{$kind}_period";
            $period = data_get($values, $periodField);
            if (filled($period)) {
                $normalizedPeriod = match (mb_strtolower(trim((string) $period))) {
                    'hourly', 'uur' => CompensationPeriod::Hour->value,
                    'daily', 'dag' => CompensationPeriod::Day->value,
                    'weekly' => CompensationPeriod::Week->value,
                    'monthly', 'maand' => CompensationPeriod::Month->value,
                    'yearly', 'annual', 'annually', 'jaar' => CompensationPeriod::Year->value,
                    default => mb_strtolower(trim((string) $period)),
                };
                $normalizedPeriod = CompensationPeriod::tryFrom($normalizedPeriod)?->value;
                data_set($values, $periodField, $normalizedPeriod);
                if ($normalizedPeriod === null) {
                    $warnings[] = ['code' => "{$kind}_period_unresolved", 'field' => $periodField, 'message' => 'Compensatieperiode is onbekend en daarom niet vergelijkbaar.'];
                }
            } elseif ($min !== null || $max !== null) {
                $warnings[] = ['code' => "{$kind}_period_unresolved", 'field' => $periodField, 'message' => 'Compensatieperiode ontbreekt of is onbekend.'];
            }
        }

        $basis = data_get($values, 'vacancy.salary_basis');
        if (filled($basis)) {
            $normalizedBasis = SalaryBasis::tryFrom(mb_strtolower(trim((string) $basis)))?->value;
            data_set($values, 'vacancy.salary_basis', $normalizedBasis);
            if ($normalizedBasis === null) {
                $warnings[] = ['code' => 'salary_basis_unresolved', 'field' => 'vacancy.salary_basis', 'message' => 'Salarisbasis is onbekend en daarom niet vergelijkbaar als FTE-salaris.'];
            }
        }

        $data = new NormalizedVacancyData($values);
        foreach (CategoryType::cases() as $type) {
            if (! in_array($type, [CategoryType::employment_type, CategoryType::workplace, CategoryType::sector, CategoryType::function_area, CategoryType::experience, CategoryType::qualification], true)) {
                continue;
            } foreach ((array) $data->get("taxonomy.{$type->value}", []) as $value) {
                $result = $this->taxonomy->resolve($source, $type, $value);
                if (($result['unresolved'] ?? false)) {
                    $unresolved[] = ['code' => 'taxonomy_unresolved', 'field' => "taxonomy.{$type->value}", 'source_value' => $value, 'message' => $type->getLabel().': nog niet gekoppeld.'];
                } else {
                    $resolved[] = ['field' => "taxonomy.{$type->value}", 'category' => $result['category']->name, 'category_id' => $result['category']->id, 'type' => $type->value];
                }
            }
        }

        return new ImportRecordOutcome($data, $warnings, $errors, $unresolved, $resolved, $employer['company_id'] ?? null);
    }
}
