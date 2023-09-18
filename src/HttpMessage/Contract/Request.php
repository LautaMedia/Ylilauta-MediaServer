<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Contract;

interface Request extends Headers
{
    public function startTime(): int;

    public function id(): string;

    public function method(): string;

    public function uri(): Uri;

    public function serverParam(string $key, string $default = ''): string;

    public function cookie(string $key, string $default = ''): string;

    public function queryParam(string $key, string $default = ''): string;

    /** @return array<int, UploadedFile> */
    public function files(string $name): array;

    public function bodyParam(string $key, string $default = ''): string;

    /**
     * @param string $key
     * @param string[] $default
     * @return string[]
     */
    public function bodyParamArray(string $key, array $default = ['']): array;

    public function attributes(): array;

    public function attribute(string $name, string $default = ''): string;

    public function withAttribute(string $name, string $value): self;

    public function withoutAttribute(string $name): self;

    /**
     * @param array<string, string> $addedAttributes
     * @return self
     */
    public function withAttributes(array $addedAttributes): self;

    public function withHeader(string $name, string $value): self;

    public function withAddedHeader(string $name, string $value): self;
}