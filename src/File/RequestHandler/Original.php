<?php
declare(strict_types=1);

namespace MediaServer\File\RequestHandler;

use MediaServer\Config\Contract\Config;
use MediaServer\Downloader\Downloader;
use MediaServer\HeaderGenerator\FileResponseHeaders;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\Response;

final class Original implements RequestHandler
{
    public function __construct(private readonly Config $cfg)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        if ($this->cfg->useRemoteFileSource()) {
            $file = (new Downloader($this->cfg->remoteFileSourceUrl() . $request->uri()->path()))->download();
        } else {
            $file = $this->cfg->localFileSourcePath() . $request->uri()->path();
        }

        return new Response(
            $file,
            0,
            (new FileResponseHeaders($this->cfg, $file))->headers()
        );
    }
}