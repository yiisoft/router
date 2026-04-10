<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Stringable;
use Yiisoft\Http\Method;
use Yiisoft\Router\Internal\MiddlewareFilter;

use function array_splice;
use function count;
use function in_array;
use function is_array;
use function is_callable;
use function is_string;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
final class Route implements Stringable
{
    private bool $actionAdded = false;
    /**
     * @var array[]|callable[]|string[]
     * @psalm-var list<array|callable|string>
     */
    private array $middlewares = [];

    /**
     * @psalm-var list<array|callable|string>|null
     */
    private ?array $enabledMiddlewaresCache = null;

    /**
     * @var string[]
     */
    private array $methods;
    /**
     * @var string[]
     */
    private array $hosts = [];
    /**
     * @var array<string,scalar|Stringable|null>
     */
    private array $defaults = [];

    /**
     * @param array|callable|string|null $action Action handler. It is a primary middleware definition that
     * should be invoked last for a matched route.
     * @param array[]|callable[]|string[] $middlewares Middleware definitions.
     * @param array<string,scalar|Stringable|null> $defaults Parameter default values indexed by parameter names.
     * @param bool $override Marks route as override. When added it will replace existing route with the same name.
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     *
     * @param string|string[] $method HTTP method or list of methods.
     * @psalm-param list<array|callable|string> $middlewares
     */
    public function __construct(
        string|array $method,
        private string $pattern,
        array|callable|string|null $action = null,
        private ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        private bool $override = false,
        private array $disabledMiddlewares = [],
    ) {
        $methods = is_string($method) ? [$method] : $method;

        if (empty($methods)) {
            throw new InvalidArgumentException('$method cannot be empty.');
        }
        $this->assertListOfStrings($methods, 'methods');
        $this->assertMiddlewares($middlewares);
        $this->assertListOfStrings($hosts, 'hosts');
        $this->middlewares = $middlewares;
        $this->methods = $methods;
        $this->hosts = $this->normalizeHosts($hosts);
        $this->defaults = array_map(\strval(...), $defaults);
        if ($action !== null) {
            $this->middlewares[] = $action;
            $this->actionAdded = true;
        }
    }

    /**
     * Returns a string representation of the route.
     *
     * @return string String representation including name (if set), methods, hosts, and pattern.
     */
    public function __toString(): string
    {
        $result = $this->name === null
            ? ''
            : '[' . $this->name . '] ';

        if ($this->methods !== []) {
            $result .= implode(',', $this->methods) . ' ';
        }

        if (!empty($this->hosts)) {
            $quoted = array_map(static fn($host) => preg_quote($host, '/'), $this->hosts);

            if (!preg_match('/' . implode('|', $quoted) . '/', $this->pattern)) {
                $result .= implode('|', $this->hosts);
            }
        }

        $result .= $this->pattern;

        return $result;
    }

    /**
     * Returns debug information about the route.
     *
     * @return array Array with route properties for debugging.
     */
    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'methods' => $this->methods,
            'pattern' => $this->pattern,
            'hosts' => $this->hosts,
            'defaults' => $this->defaults,
            'override' => $this->override,
            'actionAdded' => $this->actionAdded,
            'middlewares' => $this->middlewares,
            'disabledMiddlewares' => $this->disabledMiddlewares,
            'enabledMiddlewares' => $this->getEnabledMiddlewares(),
        ];
    }

    /**
     * Creates a GET route.
     *
     * @param string $pattern URL pattern.
     * @return self New route instance.
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function get(string $pattern): self
    {
        return self::methods([Method::GET], $pattern);
    }

    /**
     * Creates a POST route.
     *
     * @param string $pattern URL pattern.
     * @return self New route instance.
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function post(string $pattern): self
    {
        return self::methods([Method::POST], $pattern);
    }

    /**
     * Creates a PUT route.
     *
     * @param string $pattern URL pattern.
     * @return self New route instance.
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function put(string $pattern): self
    {
        return self::methods([Method::PUT], $pattern);
    }

    /**
     * Creates a DELETE route.
     *
     * @param string $pattern URL pattern.
     * @return self New route instance.
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function delete(string $pattern): self
    {
        return self::methods([Method::DELETE], $pattern);
    }

    /**
     * Creates a PATCH route.
     *
     * @param string $pattern URL pattern.
     * @return self New route instance.
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function patch(string $pattern): self
    {
        return self::methods([Method::PATCH], $pattern);
    }

    /**
     * Creates a HEAD route.
     *
     * @param string $pattern URL pattern.
     * @return self New route instance.
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function head(string $pattern): self
    {
        return self::methods([Method::HEAD], $pattern);
    }

    /**
     * Creates an OPTIONS route.
     *
     * @param string $pattern URL pattern.
     * @return self New route instance.
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function options(string $pattern): self
    {
        return self::methods([Method::OPTIONS], $pattern);
    }

    /**
     * @param string[] $methods
     *
     * @deprecated Use `new Route()` instead.
     */
    public static function methods(array $methods, string $pattern): self
    {
        return new self($methods, $pattern);
    }

    /**
     * Sets the route name.
     *
     * @param string $name Route name.
     * @return self New instance with the specified name.
     */
    public function name(string $name): self
    {
        $route = clone $this;
        $route->name = $name;
        return $route;
    }

    /**
     * Sets the URL pattern.
     *
     * @param string $pattern URL pattern.
     * @return self New instance with the specified pattern.
     */
    public function pattern(string $pattern): self
    {
        $new = clone $this;
        $new->pattern = $pattern;
        return $new;
    }

    /**
     * Adds a host requirement.
     *
     * @param string $host Host name to match.
     * @return self New instance with the specified host.
     */
    public function host(string $host): self
    {
        return $this->hosts($host);
    }

    /**
     * Sets host requirements.
     *
     * @param string ...$hosts Host names to match.
     * @return self New instance with the specified hosts.
     */
    public function hosts(string ...$hosts): self
    {
        $route = clone $this;
        $route->hosts = $this->normalizeHosts($hosts);

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
     * @psalm-param array<string,null|Stringable|scalar> $defaults
     */
    public function defaults(array $defaults): self
    {
        $route = clone $this;
        $route->defaults = array_map(\strval(...), $defaults);
        return $route;
    }

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     * If no actions have been added, the middleware is added to the end of the list. Otherwise, it is added before the action.
     */
    public function middleware(array|callable|string ...$definition): self
    {
        $route = clone $this;
        if ($this->actionAdded) {
            /**
             * @psalm-suppress PropertyTypeCoercion Keys in the replacement array are not preserved.
             * @infection-ignore-all
             */
            array_splice(
                $route->middlewares,
                offset: count($route->middlewares) - 1,
                length: 0,
                replacement: $definition,
            );
        } else {
            array_push(
                $route->middlewares,
                ...array_values($definition),
            );
        }

        $route->enabledMiddlewaresCache = null;

        return $route;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route. Last added handlers will be
     * executed first.
     *
     * Passed definitions will be added to beginning. For example:
     *
     * ```php
     * // Resulting middleware stack order: Middleware1, Middleware2, Middleware3
     * Route::get('/')
     *   ->middleware(Middleware3::class)
     *   ->prependMiddleware(Middleware1::class, Middleware2::class)
     * ```
     */
    public function prependMiddleware(array|callable|string ...$definition): self
    {
        $route = clone $this;
        array_unshift(
            $route->middlewares,
            ...array_values($definition),
        );

        $route->enabledMiddlewaresCache = null;

        return $route;
    }

    /**
     * Appends action handler. It is a primary middleware definition that should be invoked last for a matched route.
     */
    public function action(array|callable|string $middlewareDefinition): self
    {
        $route = clone $this;
        $route->middlewares[] = $middlewareDefinition;
        $route->actionAdded = true;
        $route->enabledMiddlewaresCache = null;
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
            ...array_values($definition),
        );

        $route->enabledMiddlewaresCache = null;

        return $route;
    }

    /**
     * Returns route data by key.
     *
     * @param string $key Data key to retrieve (`name`, `pattern`, `host`, `hosts`, `methods`, `defaults`, `override`,
     * `hasMiddlewares`, `enabledMiddlewares`).
     * @return mixed The requested data.
     * @throws InvalidArgumentException If the key is unknown.
     */
    public function getData(string $key): mixed
    {
        return match ($key) {
            'name' => $this->name
                ?? (implode(', ', $this->methods) . ' ' . implode('|', $this->hosts) . $this->pattern),
            'pattern' => $this->pattern,
            'host' => $this->hosts[0] ?? null,
            'hosts' => $this->hosts,
            'methods' => $this->methods,
            'defaults' => $this->defaults,
            'override' => $this->override,
            'hasMiddlewares' => $this->middlewares !== [],
            'enabledMiddlewares' => $this->getEnabledMiddlewares(),
            default => throw new InvalidArgumentException('Unknown data key: ' . $key),
        };
    }

    /**
     * @param string[] $hosts
     *
     * @return array<array-key, string>
     */
    private function normalizeHosts(array $hosts): array
    {
        $normalizedHosts = [];
        foreach ($hosts as $host) {
            $host = rtrim($host, '/');

            if ($host !== '' && !in_array($host, $normalizedHosts, true)) {
                $normalizedHosts[] = $host;
            }
        }
        return $normalizedHosts;
    }

    /**
     * @psalm-assert array<string> $items
     */
    private function assertListOfStrings(array $items, string $argument): void
    {
        foreach ($items as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException('Invalid $' . $argument . ' provided, list of string expected.');
            }
        }
    }

    /**
     * @psalm-assert array<array|callable|string> $middlewareDefinitions
     */
    private function assertMiddlewares(array $middlewareDefinitions): void
    {
        /** @var mixed $middlewareDefinition */
        foreach ($middlewareDefinitions as $middlewareDefinition) {
            if (is_string($middlewareDefinition)) {
                continue;
            }

            if (is_callable($middlewareDefinition) || is_array($middlewareDefinition)) {
                continue;
            }

            throw new InvalidArgumentException(
                'Invalid $middlewareDefinitions provided, list of string or array or callable expected.',
            );
        }
    }

    /**
     * @return array[]|callable[]|string[]
     * @psalm-return list<array|callable|string>
     */
    private function getEnabledMiddlewares(): array
    {
        if ($this->enabledMiddlewaresCache !== null) {
            /** @infection-ignore-all */
            return $this->enabledMiddlewaresCache;
        }

        $this->enabledMiddlewaresCache = MiddlewareFilter::filter($this->middlewares, $this->disabledMiddlewares);

        return $this->enabledMiddlewaresCache;
    }
}
