<?php

namespace App\Imports\Mapping;

use App\Models\ImportMapping;

class MappingCompletion
{
    public function for(?ImportMapping $mapping): string
    {
        if ($mapping === null || ! $mapping->exists || $mapping->fields()->count() === 0) {
            return 'Niet geconfigureerd';
        }
        $keys = $mapping->fields()->pluck('destination_key')->all();

        return in_array('source_reference', $keys, true) && in_array('vacancy.title', $keys, true) ? 'Klaar voor preview' : 'Onvolledig';
    }
}
