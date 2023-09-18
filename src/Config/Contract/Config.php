<?php
declare(strict_types=1);

namespace MediaServer\Config\Contract;

interface Config
{
    public function redirectRootTo(): string;

    public function allowOrigin(): string;

    public function fileSourceUrl(): string;

    public function cachePath(): string;

    public function cacheDeleteKey(): string;

    public function thumbQuality(string $format): int;

    /**
     * @return array<int>
     */
    public function allowedSizes(): array;

    /**
     * @return array<string>
     */
    public function allowedFiletypes(): array;
}