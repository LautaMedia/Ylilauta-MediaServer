<?php
declare(strict_types=1);

namespace MediaServer\RequestValidator\Middleware;

use MediaServer\Config\Contract\Config;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\EmptyResponse;

use function in_array;
use function ltrim;
use function pathinfo;

use const PATHINFO_EXTENSION;

final class RequestValidator implements RequestHandler
{
    public function __construct(
        private readonly Config $cfg,
        private readonly RequestHandler $next
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        if ($request->uri()->query() !== '') {
            return new EmptyResponse(404);
        }

        $extension = pathinfo(ltrim($request->uri()->path(), '/'), PATHINFO_EXTENSION);

        if (!in_array($extension, $this->cfg->allowedFiletypes(), true)) {
            return new EmptyResponse(404);
        }

        return $this->next->handle($request);
    }
}