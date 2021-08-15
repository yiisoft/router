<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\UriInterface;

interface RouterInterface
{
    /**
     * Returns the current Route object
     *
     * @return RouteParametersInterface|null current route
     */
    public function getCurrentRoute(): ?RouteParametersInterface;

    /**
     * Returns current URI
     *
     * @return UriInterface|null current URI
     */
    public function getCurrentUri(): ?UriInterface;

    public function setCurrentRoute(RouteParametersInterface $currentRoute): void;

    public function setCurrentUri(UriInterface $currentUri): void;
}
