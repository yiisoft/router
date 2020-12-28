<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

final class Group implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    protected array $items = [];
    protected ?string $prefix;

    /**
     * @var array
     * @psalm-var array<array|callable|MiddlewareInterface|string>
     */
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
     * @param array|callable $routes
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function create(?string $prefix = null, $routes = [], MiddlewareDispatcher $dispatcher = null): self
    {
        if (\is_callable($routes)) {
            $callback = $routes;
        } elseif (is_array($routes)) {
            $callback = static function (self $group) use (&$routes) {
                foreach ($routes as $route) {
                    if ($route instanceof Route) {
                        $group->addRoute($route);
                    } elseif ($route instanceof self) {
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
     * @param array|callable|MiddlewareInterface|string $middleware
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

        if (is_scalar($middleware)) {
            $type = gettype($middleware) . ' with value "' . $middleware . '"';
        } elseif (is_object($middleware)) {
            $type = 'an instance of ' . get_class($middleware);
        } else {
            $type = gettype($middleware);
        }

        throw new InvalidArgumentException("Parameter should be either PSR middleware class name or a callable, $type given.");
    }

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        return is_array($definition) && array_keys($definition) === [0, 1] && in_array($definition[1], get_class_methods($definition[0]) ?? [], true);
    }

    /**
     * Adds a handler middleware that should be invoked for a matched route.
     * Last added handler will be executed first.
     *
     * @param array|callable|MiddlewareInterface|string $middleware An instance or a name of PSR-15 middleware,
     * handler action (an array of [handlerClass, handlerMethod]) or a callable with
     * `function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface` signature.
     * For handler action and callable typed parameters are automatically injected using dependency
     * injection container passed to the route. Current request and handler could be obtained by
     * type-hinting for {@see ServerRequestInterface} and {@see RequestHandlerInterface}.
     *
     * @return self
     */
    public function addMiddleware($middleware): self
    {
        $this->validateMiddleware($middleware);
        $this->middlewares[] = $middleware;

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

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
