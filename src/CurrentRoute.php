<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\UriInterface;

final class CurrentRoute
{
    private ?RouteParametersInterface $route = null;
    /**
     * Current URI
     *
     * @var UriInterface|null
     */
    private ?UriInterface $uri = null;

    /**
     * Returns the current Route object
     *
     * @return RouteParametersInterface|null current route
     */
    public function getRoute(): ?RouteParametersInterface
    {
        return $this->route;
    }

    /**
     * Returns current URI
     *
     * @return UriInterface|null current URI
     */
    public function getUri(): ?UriInterface
    {
        return $this->uri;
    }

    public function setRoute(RouteParametersInterface $route): void
    {
        $this->route = $route;
    }

    /**
     * @param UriInterface|null $uri
     */
    public function setUri(?UriInterface $uri): void
    {
        $this->uri = $uri;
    }
}
