<?php

namespace App\Imports;

use App\Imports\Data\SourcePayload;
use App\Imports\Exceptions\UnreadableSourceException;
use App\Models\ImportSource;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class LocalSourceLoader
{
    public function forSource(ImportSource $source): SourcePayload
    {
        $path = data_get($source->configuration, 'source_path');

        if (is_string($path) && $path !== '') {
            return $this->fromPrivatePath($path);
        }

        // Retained for controlled repository fixtures and legacy development configuration.
        $samplePath = data_get($source->configuration, 'sample_path');
        if (is_string($samplePath) && is_file($samplePath)) {
            return SourcePayload::fromPath($samplePath);
        }

        throw new UnreadableSourceException('The import source file is missing or unreadable.');
    }

    public function fromPrivatePath(string $path): SourcePayload
    {
        if ($path === '' || str_contains(str_replace('\\', '/', $path), '..')) {
            throw new UnreadableSourceException('The import source path is invalid.');
        }

        $disk = Storage::disk('local');

        if (! $disk instanceof FilesystemAdapter || ! $disk->exists($path)) {
            throw new UnreadableSourceException('The import source file is missing or unreadable.');
        }

        return SourcePayload::fromPath($disk->path($path));
    }
}
