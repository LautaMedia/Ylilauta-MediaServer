<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Message;

use MediaServer\HttpMessage\Contract\Response as ResponseInterface;

final class EmptyResponse implements ResponseInterface
{
    private ResponseInterface $response;

    /**
     * @param int $statusCode
     * @param array<string, array<int, string>> $headers
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = []
    ) {
        $this->response = new Response('', $statusCode, $headers);
    }

    public function body(): string
    {
        return $this->response->body();
    }

    public function headers(): array
    {
        return $this->response->headers();
    }

    public function header(string $name): array
    {
        return $this->response->header($name);
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->response = $new->response->withHeader($name, $value);

        return $new;
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->response = $new->response->withAddedHeader($name, $value);

        return $new;
    }

    public function statusCode(): int
    {
        return $this->response->statusCode();
    }
}