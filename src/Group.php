<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

use function get_class;
use function in_array;
use function is_object;

final class Group implements GroupInterface
{
    /**
     * @var Group[]|Route[]
     */
    private array $items = [];
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
        $new = clone $this;
        foreach ($routes as $route) {
            if ($route instanceof Route || $route instanceof self) {
                if (!$route->hasDispatcher() && $new->hasDispatcher()) {
                    $route = $route->withDispatcher($new->dispatcher);
                }
                $new->items[] = $route;
            } else {
                $type = is_object($route) ? get_class($route) : gettype($route);
                throw new InvalidArgumentException(
                    sprintf('Route should be either an instance of Route or Group, %s given.', $type)
                );
            }
        }

        $new->routesAdded = true;

        return $new;
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): GroupInterface
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

    public function withAutoOptions(...$middlewares): GroupInterface
    {
        if (!$this->routesAdded) {
            throw new RuntimeException('withAutoOptions() can not be used before routes().');
        }
        $group = clone $this;
        $pattern = '';
        foreach ($group->items as $index => $item) {
            if ($item instanceof self) {
                $item = $item->withAutoOptions(...$middlewares);
                $group->items[$index] = $item;
            } else {
                // Avoid duplicates
                if ($pattern === $item->getPattern() || in_array(Method::OPTIONS, $item->getMethods(), true)) {
                    continue;
                }
                $pattern = $item->getPattern();
                $route = Route::options($pattern);
                foreach ($middlewares as $middleware) {
                    $route = $route->middleware($middleware);
                }
                $group->items[] = $route;
            }
        }

        return $group;
    }

    public function hasDispatcher(): bool
    {
        return $this->dispatcher !== null;
    }

    public function middleware($middlewareDefinition): GroupInterface
    {
        if ($this->routesAdded) {
            throw new RuntimeException('middleware() can not be used after routes().');
        }
        $new = clone $this;
        array_unshift($new->middlewareDefinitions, $middlewareDefinition);
        return $new;
    }

    public function prependMiddleware($middlewareDefinition): GroupInterface
    {
        $new = clone $this;
        $new->middlewareDefinitions[] = $middlewareDefinition;
        $new->middlewareAdded = true;
        return $new;
    }

    public function namePrefix(string $namePrefix): GroupInterface
    {
        $new = clone $this;
        $new->namePrefix = $namePrefix;
        return $new;
    }

    public function host(string $host): GroupInterface
    {
        $new = clone $this;
        $new->host = rtrim($host, '/');
        return $new;
    }

    public function disableMiddleware($middlewareDefinition): GroupInterface
    {
        $new = clone $this;
        $new->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $new;
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
