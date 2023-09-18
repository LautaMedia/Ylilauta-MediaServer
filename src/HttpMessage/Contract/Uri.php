<?php
declare(strict_types=1);

namespace MediaServer\HttpMessage\Contract;

interface Uri
{
    public function scheme(): string;

    public function authority(): string;

    public function userInfo(): string;

    public function host(): string;

    public function hasPort(): bool;

    public function port(): int;

    public function path(): string;

    public function query(): string;

    public function fragment(): string;

    public function __toString(): string;
}