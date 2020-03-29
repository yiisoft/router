<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Middleware\Callback;

final class Group implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    protected array $items = [];
    protected ?string $prefix;
    protected array $middlewares = [];
    private ?ContainerInterface $container = null;

    private function __construct(?string $prefix = null, ?callable $callback = null, ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->prefix = $prefix;

        if ($callback !== null) {
            $callback($this);
        }
    }

    /**
     * Create a new instance
     *
     * @param string $prefix
     * @param callable|array $routes
     * @param ContainerInterface $container
     *
     * @return self
     */
    final public static function create(?string $prefix = null, $routes = [], ContainerInterface $container = null): self
    {
        if (\is_callable($routes)) {
            $callback = $routes;
        } elseif (is_array($routes)) {
            $callback = static function (Group $group) use (&$routes) {
                foreach ($routes as $route) {
                    if ($route instanceof Route) {
                        $group->addRoute($route);
                    } elseif ($route instanceof Group) {
                        $group->addGroup($route);
                    } else {
                        throw new InvalidArgumentException('Route should be either instance of Route or Group.');
                    }
                }
            };
        } else {
            $callback = null;
        }

        return new self($prefix, $callback, $container);
    }

    final public function withContainer(ContainerInterface $container): self
    {
        $group = clone $this;
        $group->container = $container;
        foreach ($group->items as $index => $item) {
            if (!$item->hasContainer()) {
                $item = $item->withContainer($container);
                $group->items[$index] = $item;
            }
        }

        return $group;
    }

    final public function hasContainer(): bool
    {
        return $this->container !== null;
    }

    final public function addRoute(Route $route): self
    {
        if (!$route->hasContainer() && $this->hasContainer()) {
            $route = $route->withContainer($this->container);
        }
        $this->items[] = $route;
        return $this;
    }

    final public function addGroup(Group $group): self
    {
        if (!$group->hasContainer() && $this->hasContainer()) {
            $group = $group->withContainer($this->container);
        }
        $this->items[] = $group;
        return $this;
    }

    /**
     * @param MiddlewareInterface|callable|string|array $middleware
     */
    private function validateMiddleware($middleware): void
    {
        if (
            is_string($middleware) && is_subclass_of($middleware, MiddlewareInterface::class)
        ) {
            return;
        }

        if (is_callable($middleware)) {
            return;
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException('Parameter should be either PSR middleware instance, PSR middleware class name, handler action or a callable.');
        }
    }

    /**
     * @param callable|MiddlewareInterface $middleware
     * @return $this
     */
    final public function addMiddleware($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @return Route|Group[]
     */
    final public function getItems(): array
    {
        return $this->items;
    }

    final public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    final public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
