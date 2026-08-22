<?php

namespace App\Imports\Preview;

use App\Imports\Mapping\NormalizationResult;

final readonly class PreviewRecord
{
    public function __construct(public int|string $position, public NormalizationResult $result, public array $provenance, public array $source) {}

    public function status(): string
    {
        return $this->result->errors !== [] ? 'Fout' : ($this->result->warnings !== [] ? 'Waarschuwing' : 'Klaar');
    }
}
