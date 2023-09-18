<?php
declare(strict_types=1);

namespace MediaServer\RequestValidator\Middleware;

use MediaServer\Config\Contract\Config;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\EmptyResponse;

final class KeyVerifier implements RequestHandler
{
    public function __construct(
        private Config $cfg,
        private RequestHandler $next
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        if (($request->header('X-Key')[0] ?? '') !== $this->cfg->cacheDeleteKey()) {
            return new EmptyResponse(403);
        }

        return $this->next->handle($request);
    }
}