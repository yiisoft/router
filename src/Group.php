<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;

final class Group implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    protected array $items = [];
    protected ?string $prefix;
    protected array $middlewares = [];
    private ?MiddlewareDispatcher $dispatcher = null;

    private function __construct(?string $prefix = null, ?callable $callback = null, MiddlewareDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        $this->prefix = $prefix;

        if ($callback !== null) {
            $callback($this);
        }
    }

    /**
     * Create a new instance
     *
     * @param string|null $prefix
     * @param callable|array $routes
     * @param MiddlewareDispatcher $dispatcher
     *
     * @return self
     */
    public static function create(?string $prefix = null, $routes = [], MiddlewareDispatcher $dispatcher = null): self
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

        return new self($prefix, $callback, $dispatcher);
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $group = clone $this;
        $group->dispatcher = $dispatcher;
        foreach ($this->items as $index => $item) {
            if (!$item->hasDispatcher()) {
                $item = $item->withDispatcher($dispatcher);
                $group->items[$index] = $item;
            }
        }

        return $group;
    }

    public function hasDispatcher(): bool
    {
        return $this->dispatcher !== null;
    }

    public function addRoute(Route $route): self
    {
        if (!$route->hasDispatcher() && $this->hasDispatcher()) {
            $route->injectDispatcher($this->dispatcher);
        }
        $this->items[] = $route;
        return $this;
    }

    public function addGroup(Group $group): self
    {
        if (!$group->hasDispatcher() && $this->hasDispatcher()) {
            $group = $group->withDispatcher($this->dispatcher);
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

        if ($this->isCallable($middleware) && (!is_array($middleware) || !is_object($middleware[0]))) {
            return;
        }

        throw new InvalidArgumentException('Parameter should be either PSR middleware class name or a callable.');
    }

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        return is_array($definition) && array_keys($definition) === [0, 1] && in_array($definition[1], get_class_methods($definition[0]) ?? [], true);
    }

    /**
     * @param callable|MiddlewareInterface $middleware
     * @return $this
     */
    public function addMiddleware($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @return Route|Group[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
