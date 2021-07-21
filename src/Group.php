<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use RuntimeException;
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
        $callback = static function (self $group) use (&$routes) {
            foreach ($routes as $route) {
                if ($route instanceof Route || $route instanceof self) {
                    if (!$route->hasDispatcher() && $group->hasDispatcher()) {
                        $route = $route->withDispatcher($group->dispatcher);
                    }
                    $group->items[] = $route;
                } else {
                    $type = is_object($route) ? get_class($route) : gettype($route);
                    throw new InvalidArgumentException(
                        sprintf('Route should be either an instance of Route or Group, %s given.', $type)
                    );
                }
            }
        };

        $new = clone $this;
        $callback($new);
        $new->routesAdded = true;

        return $new;
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): GroupInterface
    {
        $group = clone $this;
        $group->dispatcher = $dispatcher;
        foreach ($group->items as $index => $route) {
            if (!$route->hasDispatcher()) {
                $route = $route->withDispatcher($dispatcher);
                $group->items[$index] = $route;
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
        $new = clone $this;
        array_unshift($new->middlewareDefinitions, $middlewareDefinition);
        return $new;
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
