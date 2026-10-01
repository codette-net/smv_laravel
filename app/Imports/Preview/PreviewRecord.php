<?php

namespace App\Imports\Preview;

use App\Imports\Mapping\NormalizationResult;
use App\Imports\Validation\ImportRecordOutcome;

final readonly class PreviewRecord
{
    public function __construct(public int|string $position, public NormalizationResult $result, public ImportRecordOutcome $outcome, public array $provenance, public array $source) {}

    public function status(): string
    {
        return match ($this->outcome->status()) {
            'ready' => 'Klaar voor import', 'warning' => 'Waarschuwing', 'needs_resolution' => 'Actie vereist', default => 'Fout'
        };
    }
}
