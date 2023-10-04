<?php
declare(strict_types=1);

namespace MediaServer\Router;

use MediaServer\HttpMessage\Contract\Request;
use MediaServer\HttpMessage\Contract\RequestHandler;
use MediaServer\HttpMessage\Contract\Response as ResponseInterface;
use RuntimeException;

use function array_filter;
use function array_shift;
use function preg_match;

use const ARRAY_FILTER_USE_KEY;

final class RegexUrlMatch implements RequestHandler
{
    /**
     * @param array<string, callable> $routes
     */
    public function __construct(
        private readonly string $matchString,
        private readonly array $routes
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $previousRegex = $request->attribute('previousRegex', '^');
        foreach ($this->routes as $routeUrl => $routeTo) {
            if (!preg_match("#{$previousRegex}{$routeUrl}#", $this->matchString, $routeMatches)) {
                continue;
            }

            // Remove full request uri from matches
            array_shift($routeMatches);

            // Remove numbered matches
            $routeMatches = array_filter($routeMatches, '\is_string', ARRAY_FILTER_USE_KEY);
            /** @var array<string, string> $attributes */
            $attributes = $routeMatches;
            $attributes['previousRegex'] = $previousRegex . $routeUrl;

            $request = $request->withAttributes($attributes);

            /** @var RequestHandler $handler */
            $handler = $routeTo();
            break;
        }

        if (!isset($handler) || !($handler instanceof RequestHandler)) {
            throw new RuntimeException('', 404);
        }

        return $handler->handle($request);
    }
}