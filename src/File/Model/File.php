<?php
declare(strict_types=1);

namespace MediaServer\File\Model;

final class File
{
    public function __construct(
        private readonly string $path,
        private readonly bool $isTempFile
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function isTempFile(): bool
    {
        return $this->isTempFile;
    }
}