<?php

declare(strict_types=1);

namespace Yiisoft\Router\Builder;

use RuntimeException;
use Yiisoft\Router\Group;
use Yiisoft\Router\RoutableInterface;
use Yiisoft\Router\Route;

/**
 * GroupBuilder allows you to build group of routes using a flexible syntax.
 */
final class GroupBuilder implements RoutableInterface
{
    /**
     * @var Group[]|RoutableInterface[]|Route[]
     */
    private array $routes = [];

    /**
     * @var array[]|callable[]|string[]
     * @psalm-var list<array|callable|string>
     */
    private array $middlewares = [];

    private array $disabledMiddlewares = [];

    /**
     * @var string[]
     */
    private array $hosts = [];
    private bool $routesAdded = false;
    private bool $middlewareAdded = false;

    /**
     * @var array|callable|string|null Middleware definition for CORS requests.
     */
    private $corsMiddleware = null;

    private function __construct(
        private readonly ?string $prefix = null,
        private ?string $namePrefix = null,
    ) {
    }

    /**
     * Create a new group instance.
     *
     * @param string|null $prefix URL prefix to prepend to all routes of the group.
     */
    public static function create(?string $prefix = null, ?string $namePrefix = null): self
    {
        return new self($prefix, $namePrefix);
    }

    public function routes(Group|Route|RoutableInterface ...$routes): self
    {
        if ($this->middlewareAdded) {
            throw new RuntimeException('routes() can not be used after prependMiddleware().');
        }

        $new = clone $this;
        $new->routes = $routes;
        $new->routesAdded = true;

        return $new;
    }

    /**
     * Adds a middleware definition that handles CORS requests.
     * If set, routes for {@see Method::OPTIONS} request will be added automatically.
     *
     * @param array|callable|string|null $middlewareDefinition Middleware definition for CORS requests.
     */
    public function withCors(array|callable|string|null $middlewareDefinition): self
    {
        $group = clone $this;
        $group->corsMiddleware = $middlewareDefinition;

        return $group;
    }

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     */
    public function middleware(array|callable|string ...$definition): self
    {
        if ($this->routesAdded) {
            throw new RuntimeException('middleware() can not be used after routes().');
        }

        $new = clone $this;
        array_push(
            $new->middlewares,
            ...array_values($definition)
        );

        return $new;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     */
    public function prependMiddleware(array|callable|string ...$definition): self
    {
        $new = clone $this;
        array_unshift(
            $new->middlewares,
            ...array_values($definition)
        );

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
        return $this->hosts($host);
    }

    public function hosts(string ...$hosts): self
    {
        $new = clone $this;
        $new->hosts = array_values($hosts);

        return $new;
    }

    /**
     * Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function disableMiddleware(mixed ...$definition): self
    {
        $new = clone $this;
        array_push(
            $new->disabledMiddlewares,
            ...array_values($definition),
        );

        return $new;
    }

    public function toRoute(): Group|Route
    {
        return new Group(
            prefix: $this->prefix,
            namePrefix: $this->namePrefix,
            routes: $this->routes,
            middlewares: $this->middlewares,
            hosts: $this->hosts,
            disabledMiddlewares: $this->disabledMiddlewares,
            corsMiddleware: $this->corsMiddleware
        );
    }
}
