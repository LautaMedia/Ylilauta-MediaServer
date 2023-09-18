<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Message;

use MediaServer\HttpMessage\Contract\Headers as HeadersInterface;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;

final class Response implements ResponseInterface
{
    private HeadersInterface $headers;

    /**
     * @param string $body
     * @param int $statusCode
     * @param array<string, array<int, string>> $headers
     */
    public function __construct(
        private string $body = '',
        private int $statusCode = 200,
        array $headers = []
    ) {
        $this->headers = new Headers($headers);
    }

    public function body(): string
    {
        return $this->body;
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

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}