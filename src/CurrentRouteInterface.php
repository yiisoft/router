<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\UriInterface;

interface CurrentRouteInterface
{
    /**
     * Returns the current route name.
     *
     * @return string|null The current route name.
     */
    public function getName(): ?string;

    /**
     * Returns the current route object.
     *
     * @return RouteParametersInterface|null The current route.
     */
    public function getRoute(): ?RouteParametersInterface;

    /**
     * Returns the current URI.
     *
     * @return UriInterface|null The current URI.
     */
    public function getUri(): ?UriInterface;

    /**
     * Returns the current route parameters.
     *
     * @return array
     */
    public function getParameters(): array;

    /**
     * Returns the current route parameter.
     *
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getParameter(string $name, $default = null);
}
