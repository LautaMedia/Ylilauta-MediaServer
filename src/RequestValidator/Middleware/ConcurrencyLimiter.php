<?php
declare(strict_types=1);

namespace MediaServer\RequestValidator\Middleware;

use MediaServer\Config\Contract\Config;
use MediaServer\HeaderGenerator\FileResponseHeaders;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\Response;

use function fclose;
use function flock;
use function fopen;
use function is_file;
use function md5;
use function sys_get_temp_dir;
use function unlink;

use const LOCK_EX;
use const LOCK_UN;

final class ConcurrencyLimiter implements RequestHandler
{
    public function __construct(
        private readonly Config $cfg,
        private readonly RequestHandler $next
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $cacheKey = md5($request->uri()->path());
        $lockfile = sys_get_temp_dir() . "/ylilauta-mediaserver-request-lock-{$cacheKey}";

        $fh = fopen($lockfile, "w");
        flock($fh, LOCK_EX);
        // flock should block so we should only be here after getting an exclusive lock

        // Try from cache first
        $file = "{$this->cfg->cachePath()}/{$cacheKey[-2]}{$cacheKey[-1]}/{$cacheKey[-4]}{$cacheKey[-3]}/{$cacheKey}";
        if (is_file($file)) {
            flock($fh, LOCK_UN);
            fclose($fh);

            if (is_file($lockfile)) {
                unlink($lockfile);
            }

            return new Response(
                $file,
                200,
                (new FileResponseHeaders($this->cfg, $file))->headers()
            );
        }

        $response = $this->next->handle($request);

        flock($fh, LOCK_UN);
        fclose($fh);

        if (is_file($lockfile)) {
            unlink($lockfile);
        }

        return $response;
    }
}