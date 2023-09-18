<?php
declare(strict_types=1);

namespace MediaServer\HeaderGenerator;

use MediaServer\Config\Contract\Config;

use function filesize;
use function gmdate;
use function mime_content_type;

final class FileResponseHeaders
{
    public function __construct(
        private Config $cfg,
        private string $file,
    ) {
    }

    public function headers(): array
    {
        $filesize = (string)filesize($this->file);

        return [
            'Access-Control-Allow-Origin' => [$this->cfg->allowOrigin()],
            'Accept-Ranges' => ['bytes'],
            'Cache-Control' => ['public, max-age=315360000, immutable'],
            'Content-Type' => [mime_content_type($this->file)],
            'Content-Length' => [$filesize],
            'ETag' => [(new Etag($this->file))->etag()],
            'Last-Modified' => [gmdate('D, d M Y H:i:s') . ' GMT'],
            'Content-Security-Policy' => [
                "default-src 'none';" .
                "media-src 'self';" .
                "style-src 'unsafe-inline' 'self';" .
                "frame-ancestors 'none';" .
                "sandbox allow-scripts",
            ],
            'X-Frame-Options' => ['deny'],
            'X-Permitted-Cross-Domain-Policies' => ['none'],
            'X-Content-Type-Options' => ['nosniff'],
        ];
    }
}