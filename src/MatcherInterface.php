<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ServerRequestInterface;

interface MatcherInterface
{
    public function match(ServerRequestInterface $request): MatchingResult;

    public function matchForRoutes(iterable $routes, ServerRequestInterface $request): MatchingResult;
}
