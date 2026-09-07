<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use LogicException;
use Stringable;
use Yiisoft\Http\Method;
use Yiisoft\Router\Internal\HostNormalizer;
use Yiisoft\Router\Internal\MiddlewareFilter;

use function array_splice;
use function count;
use function sprintf;
use function strval;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
class Route implements Stringable
{
    /**
     * @var string[]
     */
    private array $hosts = [];
    private bool $actionAdded = false;

    /**
     * @var array[]|callable[]|string[]
     * @psalm-var list<array|callable|string>
     */
    private array $middlewares;

    private array $disabledMiddlewares;

    /**
     * @psalm-var list<array|callable|string>|null
     */
    private ?array $enabledMiddlewaresCache = null;

    /**
     * @var array<string,string>
     */
    private array $defaults;

    /**
     * Creates a route.
     *
     * @param string $pattern URL pattern to match.
     * @param string[] $methods HTTP methods to match.
     * @param string|null $name Route name.
     * @param array|callable|string|null $action Primary middleware definition that should be invoked last for a matched route.
     * @param array[]|callable[]|string[] $middlewares Handler middleware definitions that should be invoked for a matched route.
     * @param array<string,null|Stringable|scalar> $defaults Parameter default values indexed by parameter names.
     * @param string[] $hosts Hosts that the route applies to.
     * @param bool $override Whether the route should replace an existing route with the same name.
     * @param array[]|callable[]|string[] $disabledMiddlewares Middleware definitions to exclude when the action is handled.
     */
    public function __construct(
        private string $pattern,
        private array $methods,
        private ?string $name = null,
        array|callable|string|null $action = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        private bool $override = false,
        array $disabledMiddlewares = [],
    ) {
        /** @infection-ignore-all Array keys are discarded by MiddlewareFilter::filter(). */
        $this->middlewares = array_values($middlewares);
        $this->setDefaults($defaults);
        /** @infection-ignore-all Array keys are discarded by MiddlewareFilter::filter(). */
        $this->disabledMiddlewares = array_values($disabledMiddlewares);

        $this->setHosts($hosts);

        if ($action !== null) {
            $this->middlewares[] = $action;
            $this->actionAdded = true;
        }
    }

    public function __toString(): string
    {
        $result = $this->name === null
            ? ''
            : '[' . $this->name . '] ';

        if ($this->methods !== []) {
            $result .= implode(',', $this->methods) . ' ';
        }

        if ($this->hosts) {
            $quoted = array_map(static fn($host) => preg_quote($host, '/'), $this->hosts);

            if (!preg_match('/' . implode('|', $quoted) . '/', $this->pattern)) {
                $result .= implode('|', $this->hosts);
            }
        }

        $result .= $this->pattern;

        return $result;
    }

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
     * @deprecated Use new \Yiisoft\Router\Route\Get() instead.
     */
    public static function get(string $pattern): self
    {
        /** @psalm-suppress DeprecatedMethod Retain delegation between deprecated factories for compatibility. */
        return self::methods([Method::GET], $pattern);
    }

    /**
     * @deprecated Use new \Yiisoft\Router\Route\Post() instead.
     */
    public static function post(string $pattern): self
    {
        /** @psalm-suppress DeprecatedMethod Retain delegation between deprecated factories for compatibility. */
        return self::methods([Method::POST], $pattern);
    }

    /**
     * @deprecated Use new \Yiisoft\Router\Route\Put() instead.
     */
    public static function put(string $pattern): self
    {
        /** @psalm-suppress DeprecatedMethod Retain delegation between deprecated factories for compatibility. */
        return self::methods([Method::PUT], $pattern);
    }

    /**
     * @deprecated Use new \Yiisoft\Router\Route\Delete() instead.
     */
    public static function delete(string $pattern): self
    {
        /** @psalm-suppress DeprecatedMethod Retain delegation between deprecated factories for compatibility. */
        return self::methods([Method::DELETE], $pattern);
    }

    /**
     * @deprecated Use new \Yiisoft\Router\Route\Patch() instead.
     */
    public static function patch(string $pattern): self
    {
        /** @psalm-suppress DeprecatedMethod Retain delegation between deprecated factories for compatibility. */
        return self::methods([Method::PATCH], $pattern);
    }

    /**
     * @deprecated Use new \Yiisoft\Router\Route\Head() instead.
     */
    public static function head(string $pattern): self
    {
        /** @psalm-suppress DeprecatedMethod Retain delegation between deprecated factories for compatibility. */
        return self::methods([Method::HEAD], $pattern);
    }

    /**
     * @deprecated Use new \Yiisoft\Router\Route\Options() instead.
     */
    public static function options(string $pattern): self
    {
        /** @psalm-suppress DeprecatedMethod Retain delegation between deprecated factories for compatibility. */
        return self::methods([Method::OPTIONS], $pattern);
    }

    /**
     * @param string[] $methods
     *
     * @deprecated Use new Route(pattern: $pattern, methods: $methods) instead.
     */
    public static function methods(array $methods, string $pattern): self
    {
        if (static::class !== self::class) {
            throw new LogicException(sprintf(
                'Static factory methods are only valid on %s itself, not %s. Use the constructor instead.',
                self::class,
                static::class,
            ));
        }

        return new self($pattern, $methods);
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
        $route->setHosts($hosts);

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
        $route->setDefaults($defaults);
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
     * (new \Yiisoft\Router\Route\Get('/'))
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
     * @psalm-template T as string
     *
     * @psalm-param T $key
     *
     * @psalm-return (
     *   T is ('name'|'pattern') ? string :
     *       (T is 'host' ? string|null :
     *           (T is 'hosts' ? array<array-key, string> :
     *               (T is 'methods' ? array<array-key,string> :
     *                   (T is 'defaults' ? array<string,string> :
     *                       (T is ('override'|'hasMiddlewares') ? bool :
     *                           (T is 'enabledMiddlewares' ? array<array-key,array|callable|string> : mixed)
     *                       )
     *                   )
     *               )
     *           )
     *       )
     *    )
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
     * @param array<string,null|Stringable|scalar> $defaults
     */
    private function setDefaults(array $defaults): void
    {
        $this->defaults = array_map(strval(...), $defaults);
    }

    /**
     * @param string[] $hosts
     */
    private function setHosts(array $hosts): void
    {
        $this->hosts = HostNormalizer::normalize($hosts);
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
