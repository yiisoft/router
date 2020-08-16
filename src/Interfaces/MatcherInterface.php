<?php

declare(strict_types=1);

namespace Yiisoft\Router\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\MatchingResult;

interface MatcherInterface
{
    public function match(ServerRequestInterface $request): MatchingResult;

    public function matchForCollection(RouteCollectionInterface $collection, ServerRequestInterface $request): MatchingResult;
}
