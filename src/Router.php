<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\UriInterface;

final class Router implements RouterInterface
{
    private ?RouteParametersInterface $currentRoute = null;
    /**
     * Current URI
     *
     * @var UriInterface|null
     */
    private ?UriInterface $currentUri = null;

    /**
     * Returns the current Route object
     *
     * @return RouteParametersInterface|null current route
     */
    public function getCurrentRoute(): ?RouteParametersInterface
    {
        return $this->currentRoute;
    }

    /**
     * Returns current URI
     *
     * @return UriInterface|null current URI
     */
    public function getCurrentUri(): ?UriInterface
    {
        return $this->currentUri;
    }

    public function setCurrentRoute(RouteParametersInterface $currentRoute): void
    {
        $this->currentRoute = $currentRoute;
    }

    /**
     * @param UriInterface|null $currentUri
     */
    public function setCurrentUri(?UriInterface $currentUri): void
    {
        $this->currentUri = $currentUri;
    }
}
