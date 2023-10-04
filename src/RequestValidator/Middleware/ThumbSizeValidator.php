<?php
declare(strict_types=1);

namespace MediaServer\RequestValidator\Middleware;

use MediaServer\Config\Contract\Config;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\EmptyResponse;

use function in_array;

final class ThumbSizeValidator implements RequestHandler
{
    public function __construct(
        private readonly Config $cfg,
        private readonly RequestHandler $next
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $width = (int)$request->attribute('width');

        if (!in_array($width, $this->cfg->allowedSizes())) {
            return new EmptyResponse(404);
        }

        return $this->next->handle($request);
    }
}