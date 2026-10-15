<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImportFormat: string implements HasLabel
{
    case Json = 'json';
    case Xml = 'xml';
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public function getLabel(): string
    {
        return strtoupper($this->value);
    }
}
