<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Contract;

interface Headers
{
    /** @return array<string, array<int, string>> */
    public function headers(): array;

    /** @return array<int, string> */
    public function header(string $name): array;

    public function withHeader(string $name, string $value): self;

    public function withAddedHeader(string $name, string $value): self;
}