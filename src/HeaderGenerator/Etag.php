<?php
declare(strict_types=1);

namespace MediaServer\HeaderGenerator;

use function dechex;
use function filemtime;
use function filesize;

final class Etag
{
    public function __construct(private readonly string $file)
    {
    }

    public function etag(): string
    {
        return '"' . dechex(filemtime($this->file)) . '-' . dechex(filesize($this->file)) . '"';
    }
}