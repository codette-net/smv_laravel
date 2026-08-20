<?php

namespace App\Imports;

use App\Imports\Data\SourceRecord;
use App\Imports\Exceptions\InvalidSelectionRuleException;

class RecordSelector
{
    public function matches(SourceRecord $record, ?array $configuration): bool
    {
        if (blank($configuration)) {
            return true;
        }

        if (array_is_list($configuration)) {
            $configuration = ['logic' => 'and', 'rules' => $configuration];
        }

        $rules = $configuration['rules'] ?? null;
        $logic = strtolower((string) ($configuration['logic'] ?? 'and'));

        if (! is_array($rules) || ! in_array($logic, ['and', 'or'], true)) {
            throw new InvalidSelectionRuleException('Selection rules require a rules array and an and/or logic value.');
        }

        $matches = [];
        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                throw new InvalidSelectionRuleException('Each selection rule must be an object.');
            }

            $matches[] = $this->matchesRule($record, $rule);
        }

        return $logic === 'and' ? ! in_array(false, $matches, true) : in_array(true, $matches, true);
    }

    /** @return iterable<SourceRecord> */
    public function filter(iterable $records, ?array $configuration): iterable
    {
        foreach ($records as $record) {
            if ($this->matches($record, $configuration)) {
                yield $record;
            }
        }
    }

    /** @param array<string, mixed> $rule */
    private function matchesRule(SourceRecord $record, array $rule): bool
    {
        $field = $rule['field'] ?? $rule['path'] ?? null;
        $operator = $rule['operator'] ?? null;

        if (! is_string($field) || ! is_string($operator) || ! in_array($operator, ['equals', 'not_equals', 'contains', 'in', 'exists'], true)) {
            throw new InvalidSelectionRuleException('A selection rule has an invalid field or operator.');
        }

        $value = $record->get($field);
        $expected = $rule['value'] ?? $rule['values'] ?? null;

        return match ($operator) {
            'exists' => $value !== null && $value !== [],
            'equals' => $this->equals($value, $expected),
            'not_equals' => ! $this->equals($value, $expected),
            'contains' => $this->contains($value, $expected),
            'in' => is_array($expected) && collect((array) $value)->contains(fn (mixed $actual): bool => in_array(mb_strtolower((string) $actual), array_map(fn (mixed $item): string => mb_strtolower((string) $item), $expected), true)),
        };
    }

    private function equals(mixed $actual, mixed $expected): bool
    {
        return collect((array) $actual)->contains(fn (mixed $value): bool => mb_strtolower((string) $value) === mb_strtolower((string) $expected));
    }

    private function contains(mixed $actual, mixed $expected): bool
    {
        $expectedValues = is_array($expected) ? $expected : [$expected];

        return collect((array) $actual)->contains(function (mixed $value) use ($expectedValues): bool {
            foreach ($expectedValues as $expected) {
                if (str_contains(mb_strtolower((string) $value), mb_strtolower((string) $expected))) {
                    return true;
                }
            }

            return false;
        });
    }
}
