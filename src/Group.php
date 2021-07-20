<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

use function get_class;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;

final class Group implements GroupInterface
{
    /**
     * @var Group[]|Route[]
     */
    private array $routes = [];
    private ?string $prefix;
    private array $middlewareDefinitions = [];
    private ?string $host = null;
    private ?string $namePrefix = null;
    private bool $routesAdded = false;
    private bool $middlewareAdded = false;
    private array $disabledMiddlewareDefinitions = [];
    private ?MiddlewareDispatcher $dispatcher;

    private function __construct(?string $prefix = null, MiddlewareDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        $this->prefix = $prefix;
    }

    /**
     * Create a new group instance.
     *
     * @param string|null $prefix URL prefix to prepend to all routes of the group.
     * @param MiddlewareDispatcher|null $dispatcher Middleware dispatcher to use for the group.
     *
     * @return GroupInterface
     */
    public static function create(
        ?string $prefix = null,
        MiddlewareDispatcher $dispatcher = null
    ): GroupInterface {
        return new self($prefix, $dispatcher);
    }

    public function routes(...$routes): GroupInterface
    {
        if ($this->middlewareAdded) {
            throw new RuntimeException('routes() can not be used after prependMiddleware().');
        }
        if (is_callable($routes)) {
            $callback = $routes;
        } elseif (is_array($routes)) {
            $callback = static function (self $group) use (&$routes) {
                foreach ($routes as $route) {
                    if ($route instanceof Route || $route instanceof self) {
                        if (!$route->hasDispatcher() && $group->hasDispatcher()) {
                            $route = $route->withDispatcher($group->dispatcher);
                        }
                        $group->routes[] = $route;
                    } else {
                        $type = is_object($route) ? get_class($route) : gettype($route);
                        throw new InvalidArgumentException(
                            sprintf('Route should be either an instance of Route or Group, %s given.', $type)
                        );
                    }
                }
            };
        } else {
            $callback = null;
        }

        if ($callback !== null) {
            $callback($this);
        }
        $this->routesAdded = true;

        return $this;
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): GroupInterface
    {
        $group = clone $this;
        $group->dispatcher = $dispatcher;
        foreach ($group->routes as $index => $route) {
            if (!$route->hasDispatcher()) {
                $route = $route->withDispatcher($dispatcher);
                $group->routes[$index] = $route;
            }
        }

        return $group;
    }

    public function hasDispatcher(): bool
    {
        return $this->dispatcher !== null;
    }

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function middleware($middlewareDefinition): GroupInterface
    {
        if ($this->routesAdded) {
            throw new RuntimeException('middleware() can not be used after routes().');
        }
        array_unshift($this->middlewareDefinitions, $middlewareDefinition);
        return $this;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function prependMiddleware($middlewareDefinition): GroupInterface
    {
        $this->middlewareDefinitions[] = $middlewareDefinition;
        $this->middlewareAdded = true;
        return $this;
    }

    public function namePrefix(string $namePrefix): GroupInterface
    {
        $this->namePrefix = $namePrefix;
        return $this;
    }

    public function host(string $host): GroupInterface
    {
        $this->host = rtrim($host, '/');
        return $this;
    }

    /**
     * Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function disableMiddleware($middlewareDefinition): GroupInterface
    {
        $this->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $this;
    }

    /**
     * @return Group[]|Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getNamePrefix(): ?string
    {
        return $this->namePrefix;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getMiddlewareDefinitions(): array
    {
        foreach ($this->middlewareDefinitions as $index => $definition) {
            if (in_array($definition, $this->disabledMiddlewareDefinitions, true)) {
                unset($this->middlewareDefinitions[$index]);
            }
        }

        return $this->middlewareDefinitions;
    }
}
