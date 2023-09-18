<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Contract;

interface Response extends Headers
{
    public function statusCode(): int;

    public function body(): string;

    public function withHeader(string $name, string $value): self;

    public function withAddedHeader(string $name, string $value): self;
}