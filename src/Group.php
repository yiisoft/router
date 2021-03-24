<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use function get_class;
use function in_array;
use function is_array;
use function is_callable;
use function is_object;

final class Group implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    protected array $items = [];
    protected ?string $prefix;
    protected array $middlewareDefinitions = [];

    private bool $routesAdded = false;
    private bool $middlewareAdded = false;
    private array $disabledMiddlewareDefinitions = [];
    private ?MiddlewareDispatcher $dispatcher = null;

    private function __construct(?string $prefix = null, MiddlewareDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        $this->prefix = $prefix;
    }

    /**
     * Create a new instance
     *
     * @param string|null $prefix
     * @param array|callable $routes
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function create(?string $prefix = null, MiddlewareDispatcher $dispatcher = null): self
    {
        return new self($prefix, $dispatcher);
    }

    public function routes(...$routes): self
    {
        if ($this->middlewareAdded) {
            throw new \RuntimeException('Method routes() can\'t be used after method addMiddleware()');
        }
        if (is_callable($routes)) {
            $callback = $routes;
        } elseif (is_array($routes)) {
            $callback = static function (self $group) use (&$routes) {
                foreach ($routes as $route) {
                    if ($route instanceof Route) {
                        $group->addRoute($route);
                    } elseif ($route instanceof self) {
                        $group->addGroup($route);
                    } else {
                        $type = is_object($route) ? get_class($route) : gettype($route);
                        throw new InvalidArgumentException(sprintf('Route should be either an instance of Route or Group, %s given.', $type));
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

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $group = clone $this;
        $group->dispatcher = $dispatcher;
        foreach ($group->items as $index => $item) {
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

    public function addGroup(self $group): self
    {
        if (!$group->hasDispatcher() && $this->hasDispatcher()) {
            $group = $group->withDispatcher($this->dispatcher);
        }
        $this->items[] = $group;


        return $this;
    }

    /**
     * Adds a handler middleware definition that should be invoked for a matched route.
     * Last added handler will be executed first.
     *
     * @param $middlewareDefinition mixed
     *
     * @return self
     */
    public function middleware($middlewareDefinition): self
    {
        if ($this->routesAdded) {
            throw new \RuntimeException('Method middleware() can\'t be used after method routes()');
        }
        array_unshift($this->middlewareDefinitions, $middlewareDefinition);

        return $this;
    }

    public function addMiddleware($middlewareDefinition): self
    {
        $this->middlewareDefinitions[] = $middlewareDefinition;
        $this->middlewareAdded = true;
        return $this;
    }

    public function disableMiddleware($middlewareDefinition): self
    {
        $this->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $this;
    }

    /**
     * @return Group[]|Route[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getMiddlewareDefinitions(): array
    {
        foreach ($this->middlewareDefinitions as $index => $definition) {
            if (in_array($definition, $this->disabledMiddlewareDefinitions)) {
                unset($this->middlewareDefinitions[$index]);
            }
        }

        return $this->middlewareDefinitions;
    }
}
