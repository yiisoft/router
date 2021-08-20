<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Holds information about current route e.g. matched last.
 */
final class CurrentRoute
{
    /**
     * Current Route
     *
     * @var RouteParametersInterface|null
     */
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

    /**
     * @param RouteParametersInterface $route
     */
    public function setRoute(RouteParametersInterface $route): void
    {
        if ($this->route === null) {
            $this->route = $route;
            return;
        }
        throw new RuntimeException('Can not set route since it was already set.');
    }

    /**
     * @param UriInterface $uri
     */
    public function setUri(UriInterface $uri): void
    {
        if ($this->uri === null) {
            $this->uri = $uri;
            return;
        }
        throw new RuntimeException('Can not set URI since it was already set.');
    }
}
