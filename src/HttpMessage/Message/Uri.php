<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Message;

use InvalidArgumentException;
use MediaServer\HttpMessage\Contract\Uri as UriInterface;

use function is_array;
use function parse_url;

final class Uri implements UriInterface
{
    private string $scheme;
    private string $authority;
    private string $userInfo;
    private string $host;
    private int $port;
    private string $path;
    private string $query;
    private string $fragment;

    public function __construct(string $uri)
    {
        $parsedUri = parse_url($uri);

        if (!is_array($parsedUri)) {
            throw new InvalidArgumentException("Invalid URI '{$uri}'", 1);
        }

        $this->scheme = $parsedUri['scheme'] ?? '';
        $this->authority = (!empty($parsedUri['user']) ? $parsedUri['user'] . '@' : '') . ($parsedUri['host'] ?? '') . (!empty($parsedUri['port']) ? ':' . $parsedUri['port'] : '');
        $this->userInfo = ($parsedUri['user'] ?? '') . (!empty($parsedUri['pass']) ? ':' . $parsedUri['pass'] : '');
        $this->host = $parsedUri['host'] ?? '';
        $this->port = $parsedUri['port'] ?? 0;
        $this->path = $parsedUri['path'] ?? '/';
        $this->query = $parsedUri['query'] ?? '';
        $this->fragment = $parsedUri['fragment'] ?? '';
    }

    public function scheme(): string
    {
        return $this->scheme;
    }

    public function authority(): string
    {
        return $this->authority;
    }

    public function userInfo(): string
    {
        return $this->userInfo;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        if (!$this->hasPort()) {
            throw new InvalidArgumentException('Port is not defined');
        }

        return $this->port;
    }

    public function hasPort(): bool
    {
        return $this->port !== 0;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(): string
    {
        return $this->query;
    }

    public function fragment(): string
    {
        return $this->fragment;
    }

    public function __toString(): string
    {
        $return = '';

        if (!empty($this->scheme)) {
            $return .= $this->scheme . '://';
        }

        if (!empty($this->userInfo)) {
            $return .= $this->userInfo . '@';
        }

        $return .= $this->host;

        if (!empty($this->port)) {
            $return .= ':' . $this->port;
        }

        $return .= $this->path;

        if (!empty($this->query)) {
            $return .= '?' . $this->query;
        }

        if (!empty($this->fragment)) {
            $return .= '#' . $this->fragment;
        }

        return $return;
    }
}