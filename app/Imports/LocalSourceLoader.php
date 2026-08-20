<?php

namespace App\Imports;

use App\Imports\Data\SourcePayload;
use App\Imports\Exceptions\UnreadableSourceException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class LocalSourceLoader
{
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
