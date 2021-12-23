<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use LogicException;
use Psr\Http\Message\UriInterface;

/**
 * Holds information about current route e.g. matched last.
 */
final class CurrentRoute
{
    /**
     * Current Route
     *
     * @var Route|null
     */
    private ?Route $route = null;

    /**
     * Current URI
     */
    private ?UriInterface $uri = null;

    /**
     * Current Route arguments.
     */
    private array $arguments = [];

    /**
     * Returns the current route name.
     *
     * @return string|null The current route name.
     */
    public function getName(): ?string
    {
        return $this->route !== null ? $this->route->getData('name') : null;
    }

    /**
     * Returns the current route host.
     *
     * @return string|null The current route host.
     */
    public function getHost(): ?string
    {
        return $this->route !== null ? $this->route->getData('host') : null;
    }

    /**
     * Returns the current route pattern.
     *
     * @return string|null The current route pattern.
     */
    public function getPattern(): ?string
    {
        return $this->route !== null ? $this->route->getData('pattern') : null;
    }

    /**
     * Returns the current route methods.
     *
     * @return array|null The current route methods.
     */
    public function getMethods(): ?array
    {
        return $this->route !== null ? $this->route->getData('methods') : null;
    }

    /**
     * Returns the current route defaults.
     *
     * @return array|null The current route defaults.
     */
    public function getDefaults(): ?array
    {
        return $this->route !== null ? $this->route->getData('defaults') : null;
    }

    /**
     * Returns the current route override.
     *
     * @return bool|null The current route override.
     */
    public function isOverride(): ?bool
    {
        return $this->route !== null ? $this->route->getData('override') : null;
    }

    /**
     * Returns the current route object.
     *
     * @return Route|null The current route.
     */
    public function getRoute(): ?Route
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

    /**
     * @param Route $route
     *
     * @internal
     */
    public function setRoute(Route $route): void
    {
        if ($this->route === null) {
            $this->route = $route;
            return;
        }
        throw new LogicException('Can not set route since it was already set.');
    }

    /**
     * @param UriInterface $uri
     *
     * @internal
     */
    public function setUri(UriInterface $uri): void
    {
        if ($this->uri === null) {
            $this->uri = $uri;
            return;
        }
        throw new LogicException('Can not set URI since it was already set.');
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name, string $default = null): ?string
    {
        return $this->arguments[$name] ?? $default;
    }

    /**
     * @param array $arguments
     *
     * @internal
     */
    public function setArguments(array $arguments): void
    {
        if ($this->arguments === []) {
            $this->arguments = $arguments;
            return;
        }
        throw new LogicException('Can not set arguments since it was already set.');
    }
}
