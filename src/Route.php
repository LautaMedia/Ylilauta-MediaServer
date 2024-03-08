<?php
declare(strict_types=1);

namespace MediaServer;

use MediaServer\Config\Contract\Config;
use MediaServer\File\RequestHandler\DeleteNginxCache;
use MediaServer\File\RequestHandler\Original;
use MediaServer\File\RequestHandler\ThumbImage;
use MediaServer\File\RequestHandler\Youtube;
use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use MediaServer\HttpMessage\Message\EmptyResponse;
use MediaServer\RequestValidator\Middleware\ConcurrencyLimiter;
use MediaServer\RequestValidator\Middleware\KeyVerifier;
use MediaServer\RequestValidator\Middleware\RequestValidator;
use MediaServer\RequestValidator\Middleware\ThumbSizeValidator;
use MediaServer\Router\RegexMatch;
use MediaServer\Router\StringMatch;

use function in_array;

final class Route implements RequestHandler
{
    public function __construct(private readonly Config $cfg)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        if (!in_array($request->method(), ['GET', 'DELETE'])) {
            return new EmptyResponse(405, [
                'Allow' => ['GET'],
            ]);
        }

        if ($request->uri()->path() === '/') {
            return new EmptyResponse(301, ['Location' => [$this->cfg->redirectRootTo()]]);
        }

        $cfg = $this->cfg;
        $route = new StringMatch($request->method(), [
            'GET' => static fn() => new RequestValidator($cfg, new ConcurrencyLimiter($cfg,
                new RegexMatch($request->uri()->path(), [
                    '^/ytimg(?<live>_live)?/(?<videoId>[a-zA-Z0-9\-_]+).jpg$' => static fn() => new Youtube($cfg),
                    '^/[0-9a-f]{2}/[0-9a-f]{2}/(?<filename>[0-9a-f]{16})\.(?<extension>[0-9a-z]+)$' => static fn(
                    ) => new Original($cfg),
                    '^/(?<format>[0-9a-z]+)(?<basename>/[0-9a-f]{2}/[0-9a-f]{2}/(?<filename>[0-9a-f]{16}))' .
                    '-(?<width>[\d]+)\.(?<thumbFormat>[0-9a-z]+)$' => static fn() => new ThumbSizeValidator(
                        $cfg, new ThumbImage($cfg)
                    ),
                ])
            )),
            'DELETE' => static fn() => new KeyVerifier($cfg, new DeleteNginxCache($cfg)),
        ]);

        return $route->handle($request);
    }
}