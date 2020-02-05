<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Middleware\Callback;

class Group implements RouteCollectorInterface
{
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

    final public static function create(?string $prefix, array $routes = [], ContainerInterface $container = null)
    {
        $factory = new GroupFactory($container);

        return $factory($prefix, $routes);
    }

    final public function addRoute(Route $route): void
    {
        if (!$route->hasContainer() && $this->container !== null) {
            $route = $route->setContainer($this->container);
        }
        $this->items[] = $route;
    }

    final public function addGroup(string $prefix, callable $callback): void
    {
        $this->items[] = new Group($prefix, $callback);
    }

    final public function addGroupInstance(Group $group): void
    {
        $this->items[] = $group;
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
