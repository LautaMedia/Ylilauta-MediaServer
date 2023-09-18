<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Contract;

interface UploadedFile
{
    public function moveTo(string $targetPath): void;

    public function size(): int;

    public function error(): int;

    public function clientFilename(): string;
}