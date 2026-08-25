<?php

namespace App\Imports\Contracts;

use App\Imports\Data\SourcePayload;
use App\Imports\Data\SourceRecord;
use App\Models\ImportSource;

interface ImportReader
{
    /** @return iterable<SourceRecord> */
    public function records(ImportSource $source, SourcePayload $payload): iterable;
}
