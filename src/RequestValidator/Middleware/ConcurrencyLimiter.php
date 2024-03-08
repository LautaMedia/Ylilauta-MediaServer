<?php
declare(strict_types=1);

namespace MediaServer\RequestValidator\Middleware;

use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;

use function fclose;
use function flock;
use function fopen;
use function md5;
use function sys_get_temp_dir;

use const LOCK_EX;
use const LOCK_UN;

final class ConcurrencyLimiter implements RequestHandler
{
    public function __construct(private readonly RequestHandler $next) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $lockfile = sys_get_temp_dir() . '/request-lock-' . md5((string)$request->uri());

        $fh = fopen($lockfile, "w");
        flock($fh, LOCK_EX);

        // flock should block so we should only be here after getting an exclusive lock
        $response = $this->next->handle($request);

        flock($fh, LOCK_UN);
        fclose($fh);

        return $response;
    }
}