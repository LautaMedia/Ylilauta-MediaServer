<?php
declare(strict_types=1);

namespace MediaServer\File\RequestHandler;

use MediaServer\Config\Contract\Config;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\EmptyResponse;

use function error_log;
use function is_file;
use function md5;
use function unlink;

final class DeleteNginxCache implements RequestHandler
{
    public function __construct(private readonly Config $cfg)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        ignore_user_abort(true);

        $filename = ltrim($request->uri()->path(), '/');
        $folder = "{$filename[0]}{$filename[1]}/{$filename[2]}{$filename[3]}";

        // Brute force method as we don't have any way of knowing what's in the cache...
        $cacheKeys = [];
        foreach ($this->cfg->allowedFiletypes() as $type) {
            $cacheKeys[] = md5("/{$folder}/{$filename}.{$type}");

            foreach ($this->cfg->allowedSizes() as $size) {
                $cacheKeys[] = md5("/{$type}/{$folder}/{$filename}-{$size}.jpg");
                $cacheKeys[] = md5("/{$type}/{$folder}/{$filename}-{$size}.png");
                $cacheKeys[] = md5("/{$type}/{$folder}/{$filename}-{$size}.avif");
            }
        }

        foreach ($cacheKeys as $cacheFile) {
            $file =
                "{$this->cfg->cachePath()}/" .
                "{$cacheFile[-2]}{$cacheFile[-1]}/{$cacheFile[-4]}{$cacheFile[-3]}/{$cacheFile}";

            if (is_file($file) && !unlink($file)) {
                /** @noinspection ForgottenDebugOutputInspection */
                error_log("Failed to delete cache file {$file}");
            }
        }

        return new EmptyResponse(204);
    }
}