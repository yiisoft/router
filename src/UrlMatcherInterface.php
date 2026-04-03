<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 * `UrlMatcherInterface` allows finding a matching route given a server request.
 */
interface UrlMatcherInterface
{
    /**
     * Matches a server request against registered routes.
     *
     * @param ServerRequestInterface $request The server request to match.
     * @return MatchingResult The result of matching, containing route and parameters if successful.
     */
    public function match(ServerRequestInterface $request): MatchingResult;
}
