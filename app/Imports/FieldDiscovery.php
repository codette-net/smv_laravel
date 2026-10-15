<?php

namespace App\Imports;

use App\Imports\Data\DiscoveredField;

class FieldDiscovery
{
    /** @return list<DiscoveredField> */
    public function discover(iterable $records, int $sampleLimit = 3): array
    {
        $fields = [];

        foreach ($records as $record) {
            $this->collect($record->data, '', $fields, $sampleLimit);
        }

        ksort($fields);

        return array_map(function (array $field): DiscoveredField {
            $types = array_unique($field['types']);

            return new DiscoveredField(
                $field['path'],
                count($types) === 1 ? $types[0] : 'mixed',
                $field['present'],
                $field['samples'],
            );
        }, array_values($fields));
    }

    /** @param array<string, array{path: string, present: int, samples: list<mixed>, types: list<string>}> $fields */
    private function collect(mixed $value, string $path, array &$fields, int $sampleLimit): void
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                foreach ($value as $item) {
                    $this->collect($item, $this->join($path, '*'), $fields, $sampleLimit);
                }

                return;
            }

            foreach ($value as $key => $item) {
                $this->collect($item, $this->join($path, (string) $key), $fields, $sampleLimit);
            }

            return;
        }

        if ($path === '') {
            return;
        }

        $fields[$path] ??= ['path' => $path, 'present' => 0, 'samples' => [], 'types' => []];
        $fields[$path]['present']++;
        $fields[$path]['types'][] = $this->type($value);

        if (count($fields[$path]['samples']) < $sampleLimit && ! in_array($value, $fields[$path]['samples'], true)) {
            $fields[$path]['samples'][] = $value;
        }
    }

    private function join(string $path, string $segment): string
    {
        return $path === '' ? $segment : "{$path}.{$segment}";
    }

    private function type(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'number',
            is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T].*)?$/', $value) === 1 => 'date-like',
            is_string($value) => 'string',
            default => 'mixed',
        };
    }
}
