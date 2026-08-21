<?php

namespace App\Imports\Mapping;

use App\Enums\CompensationPeriod;
use App\Imports\Data\SourceRecord;
use App\Models\ImportMapping;
use App\Models\ImportSource;
use Carbon\Carbon;
use InvalidArgumentException;

class ImportMapper
{
    public function __construct(private readonly DestinationRegistry $registry) {}

    public function map(SourceRecord $source, ImportMapping $mapping, ImportSource $importSource): NormalizationResult
    {
        $values = [];
        $warnings = [];
        $errors = [];
        foreach ($mapping->fields as $field) {
            $definition = $this->registry->get($field->destination_key);
            if (! in_array($field->operation, $definition->operations, true)) {
                throw new InvalidArgumentException("Unsupported operation for [{$definition->key}].");
            }
            $raw = $this->value($source, $field->source_paths ?? [], $field->operation, $field->configuration ?? []);
            if ($field->operation === 'transform') {
                $raw = $this->transform($raw, $field->configuration['transform'] ?? null);
            }
            if ($raw !== null && $raw !== []) {
                data_set($values, $definition->key, $this->normalize($definition->key, $raw, $warnings));
            }
        }
        foreach (['source_reference', 'vacancy.title'] as $required) {
            if (blank(data_get($values, $required))) {
                $errors[] = "Required mapping value [{$required}] is missing.";
            }
        }

        return new NormalizationResult(new NormalizedVacancyData($values), $warnings, $errors);
    }

    private function value(SourceRecord $source, array $paths, string $operation, array $config): mixed
    {
        if ($operation === 'default') {
            return $config['value'] ?? null;
        }
        $values = array_values(array_filter(array_map(fn ($path) => $source->get($path), $paths), fn ($value) => $value !== null && $value !== [] && $value !== ''));
        if ($operation === 'combine') {
            return implode($config['separator'] ?? "\n\n", array_merge(...array_map(fn ($value) => is_array($value) ? $value : [$value], $values)) ?: []);
        }

        return $values[0] ?? null;
    }

    private function transform(mixed $value, ?string $transform): mixed
    {
        return match ($transform) {
            'trim' => trim((string) $value), 'string' => (string) $value, 'integer' => (int) $value, 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE), 'date' => $value === null || $value === '' ? null : Carbon::parse($value)->toIso8601String(), 'annual_salary_to_monthly' => is_numeric($value) ? (int) round($value / 12) : throw new InvalidArgumentException('Annual salary must be numeric.'), default => throw new InvalidArgumentException('Unknown import transform.')
        };
    }

    private function normalize(string $key, mixed $value, array &$warnings): mixed
    {
        if ($key === 'source_reference') {
            return (string) $value;
        }
        if (str_ends_with($key, '_period')) {
            $period = CompensationPeriod::tryFrom(strtolower((string) $value));
            if (! $period) {
                $warnings[] = "Unknown compensation period [{$value}].";

                return null;
            }

return $period->value;
        }

        return $value;
    }
}
