<?php

namespace App\Imports\Data;

use App\Imports\Exceptions\UnreadableSourceException;

final readonly class SourcePayload
{
    private function __construct(
        public ?string $path,
        public ?string $contents,
        public ?string $origin = null,
    ) {}

    public static function fromPath(string $path): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new UnreadableSourceException('The import source file is missing or unreadable.');
        }

        return new self($path, null, $path);
    }

    public static function fromContents(string $contents, ?string $origin = null): self
    {
        return new self(null, $contents, $origin);
    }

    public function contents(): string
    {
        if ($this->contents !== null) {
            return $this->contents;
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new UnreadableSourceException('The import source file could not be read.');
        }

        return $contents;
    }

    public function path(): string
    {
        if ($this->path !== null) {
            return $this->path;
        }

        $path = tempnam(sys_get_temp_dir(), 'smv-import-');

        if ($path === false || file_put_contents($path, $this->contents()) === false) {
            throw new UnreadableSourceException('The import source could not be prepared for reading.');
        }

        return $path;
    }
}
