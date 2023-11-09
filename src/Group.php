<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Router\Internal\MiddlewareFilter;

final class Group
{
    /**
     * @var Group[]|RoutableInterface[]|Route[]
     */
    private array $routes = [];

    /**
     * @var array[]|callable[]|string[]
     * @psalm-var list<array|callable|string>
     */
    private array $middlewares = [];

    /**
     * @var string[]
     */
    private array $hosts = [];

    /**
     * @psalm-var list<array|callable|string>|null
     */
    private ?array $enabledMiddlewaresCache = null;

    /**
     * @var array|callable|string|null Middleware definition for CORS requests.
     */
    private $corsMiddleware = null;

    /**
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function __construct(
        private ?string $prefix = null,
        private ?string $namePrefix = null,
        array $routes = [],
        array $middlewares = [],
        array $hosts = [],
        private array $disabledMiddlewares = [],
        array|callable|string|null $corsMiddleware = null
    ) {
        $this->setRoutes($routes);
        $this->setMiddlewares($middlewares);
        $this->setHosts($hosts);
        $this->corsMiddleware = $corsMiddleware;
    }

    /**
     * @return Group[]|RoutableInterface[]|Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function getCorsMiddleware(): callable|array|string|null
    {
        return $this->corsMiddleware;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getNamePrefix(): ?string
    {
        return $this->namePrefix;
    }

    public function getDisabledMiddlewares(): array
    {
        return $this->disabledMiddlewares;
    }

    public function setRoutes(array $routes): self
    {
        $this->assertRoutes($routes);
        $this->routes = $routes;
        return $this;
    }

    public function setMiddlewares(array $middlewares): self
    {
        $this->assertMiddlewares($middlewares);
        $this->middlewares = $middlewares;
        $this->enabledMiddlewaresCache = null;
        return $this;
    }

    public function setHosts(array $hosts): self
    {
        foreach ($hosts as $host) {
            if (!is_string($host)) {
                throw new \InvalidArgumentException('Invalid $hosts provided, list of string expected.');
            }
            $host = rtrim($host, '/');

            if ($host !== '' && !in_array($host, $this->hosts, true)) {
                $this->hosts[] = $host;
            }
        }

        return $this;
    }

    public function setCorsMiddleware(callable|array|string|null $corsMiddleware): self
    {
        $this->corsMiddleware = $corsMiddleware;
        return $this;
    }

    public function setPrefix(?string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    public function setNamePrefix(?string $namePrefix): self
    {
        $this->namePrefix = $namePrefix;
        return $this;
    }

    public function setDisabledMiddlewares(array $disabledMiddlewares): self
    {
        $this->disabledMiddlewares = $disabledMiddlewares;
        $this->enabledMiddlewaresCache = null;
        return $this;
    }

    /**
     * @return array[]|callable[]|string[]
     * @psalm-return list<array|callable|string>
     */
    public function getEnabledMiddlewares(): array
    {
        if ($this->enabledMiddlewaresCache !== null) {
            return $this->enabledMiddlewaresCache;
        }

        return $this->enabledMiddlewaresCache = MiddlewareFilter::filter($this->middlewares, $this->disabledMiddlewares);
    }

    /**
     * @psalm-assert list<array|callable|string> $middlewares
     */
    private function assertMiddlewares(array $middlewares): void
    {
        /** @var mixed $middleware */
        foreach ($middlewares as $middleware) {
            if (is_string($middleware) || is_callable($middleware) || is_array($middleware)) {
                continue;
            }

            throw new \InvalidArgumentException(
                'Invalid $middlewares provided, list of string or array or callable expected.'
            );
        }
    }

    /**
     * @psalm-assert array<Route|Group|RoutableInterface> $routes
     */
    private function assertRoutes(array $routes): void
    {
        /** @var Group|RoutableInterface|Route $route */
        foreach ($routes as $route) {
            if ($route instanceof Route || $route instanceof self || $route instanceof RoutableInterface) {
                continue;
            }

            throw new \InvalidArgumentException(
                'Invalid $routes provided, array of `Route` or `Group` or `RoutableInterface` instance expected.'
            );
        }
    }
}
