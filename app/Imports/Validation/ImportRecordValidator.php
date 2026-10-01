<?php

namespace App\Imports\Validation;

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\CompensationPeriod;
use App\Imports\Mapping\NormalizedVacancyData;
use App\Models\ImportSource;

class ImportRecordValidator
{
    public function __construct(private readonly TaxonomyResolver $taxonomy) {}

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
        if (! $source->company) {
            $errors[] = ['code' => 'owner_missing', 'field' => 'company', 'message' => 'De importbron heeft geen eigenaar-bedrijf.'];
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
            $min = $data->get("vacancy.{$kind}_min");
            $max = $data->get("vacancy.{$kind}_max");
            $period = $data->get("vacancy.{$kind}_period");
            if (($min !== null && (! is_numeric($min) || $min < 0)) || ($max !== null && (! is_numeric($max) || $max < 0)) || ($min !== null && $max !== null && $min > $max)) {
                $errors[] = ['code' => "{$kind}_invalid", 'field' => "vacancy.{$kind}", 'message' => 'Compensatiebereik is ongeldig.'];
            } if ($min !== null || $max !== null) {
                if (! CompensationPeriod::tryFrom((string) $period)) {
                    $warnings[] = ['code' => "{$kind}_period_unresolved", 'field' => "vacancy.{$kind}_period", 'message' => 'Compensatieperiode ontbreekt of is onbekend.'];
                }
            }
        }
        foreach (CategoryType::cases() as $type) {
            if (! in_array($type, [CategoryType::employment_type, CategoryType::workplace, CategoryType::sector, CategoryType::function_area, CategoryType::experience], true)) {
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

        return new ImportRecordOutcome($data, $warnings, $errors, $unresolved, $resolved);
    }
}
