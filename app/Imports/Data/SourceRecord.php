<?php

namespace App\Imports\Data;

final readonly class SourceRecord
{
    /** @param array<string, mixed> $data */
    /** @param array<string, mixed> $context */
    public function __construct(
        public int|string $position,
        public array $data,
        public ?string $recordPath = null,
        public array $context = [],
    ) {}

    public function get(string $path, mixed $default = null): mixed
    {
        $values = $this->values($this->data, array_values(array_filter(explode('.', $path), fn (string $part): bool => $part !== '')));

        if ($values === []) {
            return $default;
        }

        return str_contains($path, '*') ? $values : $values[0];
    }

    /** @return list<mixed> */
    private function values(mixed $value, array $segments): array
    {
        if ($segments === []) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        $segment = array_shift($segments);

        if ($segment === '*') {
            return array_values(array_merge(...array_map(fn (mixed $child): array => $this->values($child, $segments), $value)) ?: []);
        }

        if (! array_key_exists($segment, $value)) {
            return [];
        }

        return $this->values($value[$segment], $segments);
    }
}
