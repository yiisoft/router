<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Middleware\Callback;

class Group implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    protected array $items = [];
    protected ?string $prefix;
    protected array $middlewares = [];
    private ?ContainerInterface $container = null;

    public function __construct(?string $prefix = null, ?callable $callback = null, ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->prefix = $prefix;

        if ($callback !== null) {
            $callback($this);
        }
    }

    final public static function create(?string $prefix, array $routes = [], ContainerInterface $container = null): self
    {
        $factory = new GroupFactory($container);

        return $factory($prefix, $routes);
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
     * @param callable|MiddlewareInterface $middleware
     * @return $this
     */
    final public function addMiddleware($middleware): self
    {
        if (is_callable($middleware)) {
            $middleware = new Callback($middleware, $this->container);
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException('Parameter should be either a PSR middleware or a callable.');
        }

        array_unshift($this->middlewares, $middleware);

        return $this;
    }

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
