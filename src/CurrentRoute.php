<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\UriInterface;

/**
 * Holds information about current route e.g. matched last.
 */
final class CurrentRoute implements CurrentRouteInterface
{
    private ?RouteParametersInterface $route = null;

    /**
     * Current URI
     */
    private ?UriInterface $uri = null;

    /**
     * Returns the current route object.
     *
     * @return RouteParametersInterface|null The current route.
     */
    public function getRoute(): ?RouteParametersInterface
    {
        return $this->route;
    }

    /**
     * Returns the current URI.
     *
     * @return UriInterface|null The current URI.
     */
    public function getUri(): ?UriInterface
    {
        return $this->uri;
    }

    public function setRoute(RouteParametersInterface $route): void
    {
        $this->route = $route;
    }

    public function setUri(?UriInterface $uri): void
    {
        $this->uri = $uri;
    }
}
