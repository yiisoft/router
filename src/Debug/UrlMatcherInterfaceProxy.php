<?php

declare(strict_types=1);

namespace Yiisoft\Router\Debug;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\UrlMatcherInterface;

final class UrlMatcherInterfaceProxy implements UrlMatcherInterface
{
    public function __construct(private UrlMatcherInterface $urlMatcher, private RouterCollector $routerCollector)
    {
    }

    public function match(ServerRequestInterface $request): MatchingResult
    {
        $timeStart = microtime(true);
        $result = $this->urlMatcher->match($request);
        $this->routerCollector->collect(microtime(true) - $timeStart);

        return $result;
    }
}
