<?php

namespace App\Imports\Data;

final readonly class DiscoveredField
{
    /** @param list<mixed> $samples */
    public function __construct(
        public string $path,
        public string $type,
        public int $present,
        public array $samples,
    ) {}

    /** @return array{path: string, type: string, present: int, samples: list<mixed>} */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'type' => $this->type,
            'present' => $this->present,
            'samples' => $this->samples,
        ];
    }
}
