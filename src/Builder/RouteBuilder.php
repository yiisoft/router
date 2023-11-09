<?php

declare(strict_types=1);

namespace Yiisoft\Router\Builder;

use Stringable;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\RoutableInterface;
use Yiisoft\Router\Route;

use function in_array;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
final class RouteBuilder implements RoutableInterface
{
    private ?string $name = null;

    /**
     * @var array|string|callable|null
     */
    private $action = null;

    /**
     * @var string[]
     */
    private array $hosts = [];

    private bool $override = false;

    private array $disabledMiddlewares = [];

    /**
     * @var array[]|callable[]|string[]
     * @psalm-var list<array|callable|string>
     */
    private array $middlewares = [];

    /**
     * @var array<array-key,scalar|Stringable|null>
     */
    private array $defaults = [];

    /**
     * @param string[] $methods
     */
    private function __construct(
        private array $methods,
        private string $pattern,
    ) {
    }

    public static function get(string $pattern): self
    {
        return self::methods([Method::GET], $pattern);
    }

    public static function post(string $pattern): self
    {
        return self::methods([Method::POST], $pattern);
    }

    public static function put(string $pattern): self
    {
        return self::methods([Method::PUT], $pattern);
    }

    public static function delete(string $pattern): self
    {
        return self::methods([Method::DELETE], $pattern);
    }

    public static function patch(string $pattern): self
    {
        return self::methods([Method::PATCH], $pattern);
    }

    public static function head(string $pattern): self
    {
        return self::methods([Method::HEAD], $pattern);
    }

    public static function options(string $pattern): self
    {
        return self::methods([Method::OPTIONS], $pattern);
    }

    /**
     * @param string[] $methods
     */
    public static function methods(array $methods, string $pattern): self
    {
        return new self(methods: $methods, pattern: $pattern);
    }

    public function name(string $name): self
    {
        $route = clone $this;
        $route->name = $name;
        return $route;
    }

    public function pattern(string $pattern): self
    {
        $new = clone $this;
        $new->pattern = $pattern;
        return $new;
    }

    public function host(string $host): self
    {
        return $this->hosts($host);
    }

    public function hosts(string ...$hosts): self
    {
        $route = clone $this;
        $route->hosts = array_values($hosts);

        return $route;
    }

    /**
     * Marks route as override. When added it will replace existing route with the same name.
     */
    public function override(): self
    {
        $route = clone $this;
        $route->override = true;
        return $route;
    }

    /**
     * Parameter default values indexed by parameter names.
     *
     * @psalm-param array<array-key,null|Stringable|scalar> $defaults
     */
    public function defaults(array $defaults): self
    {
        $route = clone $this;
        $route->defaults = $defaults;
        return $route;
    }

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     */
    public function middleware(array|callable|string ...$definition): self
    {
        $route = clone $this;
        array_push(
            $route->middlewares,
            ...array_values($definition)
        );

        return $route;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * Last added handler will be executed first.
     */
    public function prependMiddleware(array|callable|string ...$definition): self
    {
        $route = clone $this;
        array_unshift(
            $route->middlewares,
            ...array_values($definition)
        );

        return $route;
    }

    /**
     * Appends action handler. It is a primary middleware definition that should be invoked last for a matched route.
     */
    public function action(array|callable|string $middlewareDefinition): self
    {
        $route = clone $this;
        $route->action = $middlewareDefinition;
        return $route;
    }

    /**
     * Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function disableMiddleware(mixed ...$definition): self
    {
        $route = clone $this;
        array_push(
            $route->disabledMiddlewares,
            ...array_values($definition)
        );

        return $route;
    }

    public function toRoute(): Group|Route
    {
        return new Route(
            methods: $this->methods,
            pattern: $this->pattern,
            name: $this->name,
            action: $this->action,
            middlewares: $this->middlewares,
            defaults: $this->defaults,
            hosts: $this->hosts,
            override: $this->override,
            disabledMiddlewares: $this->disabledMiddlewares
        );
    }
}
