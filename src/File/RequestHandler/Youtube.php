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

final class Youtube implements RequestHandler
{
    public function __construct(private readonly Config $cfg)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        $live = $request->attribute('live');
        $videoId = $request->attribute('videoId');

        $file = (new Downloader("https://img.youtube.com/vi/{$videoId}/mqdefault{$live}.jpg"))->download();

        return new Response(
            $file,
            1, // 1 to delete $file after readfile()
            (new FileResponseHeaders($this->cfg, $file))->headers()
        );
    }
}