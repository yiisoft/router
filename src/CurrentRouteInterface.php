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
     * Returns the current route arguments.
     *
     * @return array
     */
    public function getArguments(): array;

    /**
     * Returns the current route argument.
     *
     * @param string $name The argument name.
     * @param mixed $default The default value.
     *
     * @return mixed
     */
    public function getArgument(string $name, $default = null);
}
