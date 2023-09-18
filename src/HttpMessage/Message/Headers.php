<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Message;

use MediaServer\HttpMessage\Contract\Headers as HeadersInterface;

use function array_change_key_case;
use function array_key_exists;
use function array_keys;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function substr;
use function ucwords;

use const CASE_LOWER;

final class Headers implements HeadersInterface
{
    /**
     * @param array<string, array<int, string>> $headers
     */
    public function __construct(private array $headers = [])
    {
    }

    /**
     * @param array $superglobalServer
     * @return static
     */
    public static function fromSuperglobals(array $superglobalServer): self
    {
        $headers = [];
        /** @var string $value - Technically it's not always a string, but we only care about HTTP headers which are */
        foreach ($superglobalServer as $name => $value) {
            $name = (string)$name;

            if (!str_starts_with($name, 'HTTP_')) {
                continue;
            }

            $name = substr($name, 5);
            $name = ucwords(strtolower(str_replace('_', ' ', $name)));
            $name = str_replace(' ', '-', $name);

            if (!isset($headers[$name])) {
                $headers[$name] = [];
            }
            $headers[$name][] = $value;
        }

        return new self($headers);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function header(string $name): array
    {
        if (!array_key_exists(strtolower($name), array_change_key_case($this->headers, CASE_LOWER))) {
            return [];
        }

        $targetHeader = $this->getHeaderOrigName($name);

        return $this->headers[$targetHeader];
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $targetHeader = $this->getHeaderOrigName($name);
        unset($headers[$targetHeader]);

        $new = clone $this;
        $new->headers = $headers;

        return $new->withAddedHeader($name, $value);
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $targetHeader = $this->getHeaderOrigName($name);

        if (isset($headers[$targetHeader])) {
            $headers[$targetHeader][] = $value;
        } else {
            $headers[$targetHeader] = [$value];
        }

        $new = clone $this;
        $new->headers = $headers;

        return $new;
    }

    private function getHeaderOrigName(string $name): string
    {
        foreach (array_keys($this->headers) as $headerName) {
            $lower = strtolower($headerName);

            if ($lower === strtolower($name)) {
                return $headerName;
            }
        }

        return $name;
    }
}