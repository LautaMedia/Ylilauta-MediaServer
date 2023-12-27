<?php
declare(strict_types=1);

namespace MediaServer\File\RequestHandler;

use MediaServer\Config\Contract\Config;
use MediaServer\Downloader\Downloader;
use MediaServer\HeaderGenerator\FileResponseHeaders;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\EmptyResponse;
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

        if (!is_file($file)) {
            return new EmptyResponse(404);
        }

        return new Response(
            $file,
            $this->cfg->useRemoteFileSource() ? 1 : 0, // 1 to delete $file after readfile()
            (new FileResponseHeaders($this->cfg, $file))->headers()
        );
    }
}