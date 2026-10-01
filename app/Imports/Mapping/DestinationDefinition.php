<?php

namespace App\Imports\Mapping;

final readonly class DestinationDefinition
{
    /** @param list<string> $operations */
    public function __construct(public string $key, public string $label, public string $group, public array $operations, public bool $required = false) {}
}
