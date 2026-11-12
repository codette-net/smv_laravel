<?php

namespace App\Imports;

use App\Imports\Data\SourcePayload;
use App\Imports\Exceptions\InvalidSourceException;
use App\Imports\Joobs\JoobsFeedContract;
use App\Models\ImportSource;

class SourceContractValidator
{
    public function validate(ImportSource $source, SourcePayload $payload): void
    {
        $contract = data_get($source->configuration, 'source_contract');

        if ($contract === null) {
            return;
        }

        match ($contract) {
            JoobsFeedContract::FEED_VERSION => app(JoobsFeedContract::class)->inspect($payload),
            default => throw new InvalidSourceException("The configured source contract [{$contract}] is not supported."),
        };
    }
}
