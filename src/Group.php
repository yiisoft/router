<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

use function get_class;
use function in_array;
use function is_object;

final class Group
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
    /**
     * @var mixed Middleware definition for CORS requests.
     */
    private $corsMiddleware;
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
     * @return self
     */
    public static function create(
        ?string $prefix = null,
        MiddlewareDispatcher $dispatcher = null
    ): self {
        return new self($prefix, $dispatcher);
    }

    public function routes(...$routes): self
    {
        if ($this->middlewareAdded) {
            throw new RuntimeException('routes() can not be used after prependMiddleware().');
        }
        $new = clone $this;
        foreach ($routes as $route) {
            if ($route instanceof Route || $route instanceof self) {
                if (!$route->getData('hasDispatcher') && $new->getData('hasDispatcher')) {
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

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $group = clone $this;
        $group->dispatcher = $dispatcher;
        foreach ($group->items as $index => $item) {
            if (!$item->getData('hasDispatcher')) {
                $item = $item->withDispatcher($dispatcher);
                $group->items[$index] = $item;
            }
        }

        return $group;
    }

    /**
     * Adds a middleware definition that handles CORS requests.
     * If set, routes for {@see Method::OPTIONS} request will be added automatically.
     *
     * @param mixed $middlewareDefinition Middleware definition for CORS requests.
     *
     * @return self
     */
    public function withCors($middlewareDefinition): self
    {
        $group = clone $this;
        $group->corsMiddleware = $middlewareDefinition;

        return $group;
    }

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function middleware($middlewareDefinition): self
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
    public function prependMiddleware($middlewareDefinition): self
    {
        $new = clone $this;
        $new->middlewareDefinitions[] = $middlewareDefinition;
        $new->middlewareAdded = true;
        return $new;
    }

    public function namePrefix(string $namePrefix): self
    {
        $new = clone $this;
        $new->namePrefix = $namePrefix;
        return $new;
    }

    public function host(string $host): self
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
    public function disableMiddleware($middlewareDefinition): self
    {
        $new = clone $this;
        $new->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $new;
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @internal
     */
    public function getData(string $key)
    {
        switch ($key) {
            case 'prefix':
                return $this->prefix;
            case 'namePrefix':
                return $this->namePrefix;
            case 'host':
                return $this->host;
            case 'corsMiddleware':
                return $this->corsMiddleware;
            case 'items':
                return $this->items;
            case 'hasCorsMiddleware':
                return $this->corsMiddleware !== null;
            case 'hasDispatcher':
                return $this->dispatcher !== null;
            case 'middlewareDefinitions':
                return $this->getMiddlewareDefinitions();
            default:
                throw new InvalidArgumentException('Unknown data key: ' . $key);
        }
    }

    private function getMiddlewareDefinitions(): array
    {
        foreach ($this->middlewareDefinitions as $index => $definition) {
            if (in_array($definition, $this->disabledMiddlewareDefinitions, true)) {
                unset($this->middlewareDefinitions[$index]);
            }
        }

        return $this->middlewareDefinitions;
    }
}
