<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Message;

use MediaServer\HttpMessage\Contract\Headers;
use MediaServer\HttpMessage\Contract\Request as RequestInterface;
use MediaServer\HttpMessage\Contract\UploadedFile as UploadedFileInterface;
use MediaServer\HttpMessage\Contract\Uri;

use function hrtime;
use function is_array;

final class Request implements RequestInterface
{
    private int $startTime;

    /**
     * @param string $id
     * @param Headers $headers
     * @param string $method
     * @param Uri $uri
     * @param array $serverParams
     * @param array $cookies
     * @param array $queryParams
     * @param array<string, array<int, UploadedFileInterface>> $files
     * @param array<string, string|string[]> $bodyParams
     * @param array<string, string> $attributes
     */
    public function __construct(
        private readonly string $id,
        private Headers $headers,
        private readonly string $method,
        private readonly Uri $uri,
        private readonly array $serverParams,
        private readonly array $cookies,
        private readonly array $queryParams,
        private readonly array $files,
        private readonly array $bodyParams,
        private array $attributes = []
    ) {
        $this->startTime = hrtime(true);
    }

    public function startTime(): int
    {
        return $this->startTime;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function headers(): array
    {
        return $this->headers->headers();
    }

    public function header(string $name): array
    {
        return $this->headers->header($name);
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers = $new->headers->withHeader($name, $value);

        return $new;
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers = $new->headers->withAddedHeader($name, $value);

        return $new;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): Uri
    {
        return $this->uri;
    }

    public function serverParam(string $key, string $default = ''): string
    {
        return (string)($this->serverParams[$key] ?? $default);
    }

    public function cookie(string $key, string $default = ''): string
    {
        return (string)($this->cookies[$key] ?? $default);
    }

    public function queryParam(string $key, string $default = ''): string
    {
        return (string)($this->queryParams[$key] ?? $default);
    }

    public function files(string $name): array
    {
        return $this->files[$name] ?? [];
    }

    public function bodyParam(string $key, string $default = ''): string
    {
        return $this->bodyParamArray($key, [$default])[0];
    }

    /**
     * @param string $key
     * @param string[] $default
     * @return string[]
     */
    public function bodyParamArray(string $key, array $default = ['']): array
    {
        if (!isset($this->bodyParams[$key])) {
            return $default;
        }

        if (!is_array($this->bodyParams[$key])) {
            return [$this->bodyParams[$key]];
        }

        return $this->bodyParams[$key];
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    public function attribute(string $name, string $default = ''): string
    {
        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute(string $name, string $value): self
    {
        $attributes = $this->attributes;
        $attributes[$name] = $value;

        $new = clone $this;
        $new->attributes = $attributes;

        return $new;
    }

    public function withAttributes(array $addedAttributes): self
    {
        $attributes = $this->attributes;

        foreach ($addedAttributes as $name => $value) {
            $attributes[$name] = $value;
        }

        $new = clone $this;
        $new->attributes = $attributes;

        return $new;
    }

    public function withoutAttribute(string $name): self
    {
        $attributes = $this->attributes;
        if (!isset($attributes[$name])) {
            return $this;
        }

        unset($attributes[$name]);

        $new = clone $this;
        $new->attributes = $attributes;

        return $new;
    }
}