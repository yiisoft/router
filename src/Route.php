<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Attribute;
use InvalidArgumentException;
use RuntimeException;
use Stringable;
use Yiisoft\Http\Method;

use function in_array;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route implements Stringable
{
    private bool $actionAdded = false;
    /**
     * @var callable[]|array[]|string[]
     */
    private array $builtMiddlewares = [];

    /**
     * @param array $defaults Parameter default values indexed by parameter names.
     * @param bool $override Marks route as override. When added it will replace existing route with the same name.
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function __construct(
        private array $methods,
        private string $pattern,
        private ?string $name = null,
        private array $middlewares = [],
        private array $defaults = [],
        private array $hosts = [],
        private bool $override = false,
        private array $disabledMiddlewares = [],
    ) {
    }

    public static function get(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return self::methods(
            [Method::GET],
            $pattern,
            $name,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares
        );
    }

    public static function post(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return self::methods(
            [Method::POST],
            $pattern,
            $name,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares
        );
    }

    public static function put(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return self::methods(
            [Method::PUT],
            $pattern,
            $name,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares
        );
    }

    public static function delete(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return self::methods(
            [Method::DELETE],
            $pattern,
            $name,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares
        );
    }

    public static function patch(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return self::methods(
            [Method::PATCH],
            $pattern,
            $name,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares
        );
    }

    public static function head(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return self::methods(
            [Method::HEAD],
            $pattern,
            $name,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares
        );
    }

    public static function options(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return self::methods(
            [Method::OPTIONS],
            $pattern,
            $name,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares
        );
    }

    /**
     * @param string[] $methods
     */
    public static function methods(
        array $methods,
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ): self {
        return new self(
            methods: $methods,
            pattern: $pattern,
            name: $name,
            middlewares: $middlewares,
            defaults: $defaults,
            hosts: $hosts,
            override: $override,
            disabledMiddlewares: $disabledMiddlewares
        );
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
        $route->hosts = [];

        foreach ($hosts as $host) {
            $host = rtrim($host, '/');

            if ($host !== '' && !in_array($host, $route->hosts, true)) {
                $route->hosts[] = $host;
            }
        }

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
        $route->defaults = array_map('\strval', $defaults);
        return $route;
    }

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     */
    public function middleware(array|callable|string ...$middlewareDefinition): self
    {
        if ($this->actionAdded) {
            throw new RuntimeException('middleware() can not be used after action().');
        }
        $route = clone $this;
        array_push(
            $route->middlewares,
            ...array_values($middlewareDefinition)
        );
        $route->builtMiddlewares = [];
        return $route;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * Last added handler will be executed first.
     */
    public function prependMiddleware(array|callable|string ...$middlewareDefinition): self
    {
        if (!$this->actionAdded) {
            throw new RuntimeException('prependMiddleware() can not be used before action().');
        }
        $route = clone $this;
        array_unshift(
            $route->middlewares,
            ...array_values($middlewareDefinition)
        );
        $route->builtMiddlewares = [];
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
        $route->builtMiddlewares = [];
        return $route;
    }

    /**
     * Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function disableMiddleware(mixed ...$middlewareDefinition): self
    {
        $route = clone $this;
        array_push(
            $route->disabledMiddlewares,
            ...array_values($middlewareDefinition)
        );
        $route->builtMiddlewares = [];
        return $route;
    }

    /**
     * @psalm-template T as string
     * @psalm-param T $key
     * @psalm-return (
     *   T is ('name'|'pattern') ? string :
     *       (T is 'host' ? string|null :
     *           (T is 'hosts' ? array<array-key, string> :
     *               (T is 'methods' ? array<array-key,string> :
     *                   (T is 'defaults' ? array<string,string> :
     *                       (T is ('override'|'hasMiddlewares') ? bool : mixed)
     *                   )
     *               )
     *           )
     *       )
     *    )
     */
    public function getData(string $key): mixed
    {
        return match ($key) {
            'name' => $this->name ??
                (implode(', ', $this->methods) . ' ' . implode('|', $this->hosts) . $this->pattern),
            'pattern' => $this->pattern,
            'host' => $this->hosts[0] ?? null,
            'hosts' => $this->hosts,
            'methods' => $this->methods,
            'defaults' => $this->defaults,
            'override' => $this->override,
            'hasMiddlewares' => $this->middlewares !== [],
            default => throw new InvalidArgumentException('Unknown data key: ' . $key),
        };
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
            $quoted = array_map(static fn ($host) => preg_quote($host, '/'), $this->hosts);

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
            'builtMiddlewares' => $this->builtMiddlewares,
            'disabledMiddlewares' => $this->disabledMiddlewares,
        ];
    }

    /**
     * @return callable[]|array[]|string[]
     */
    public function getBuiltMiddlewares(): array
    {
        // Don't build middlewares if we did it earlier.
        // This improves performance in event-loop applications.
        if ($this->builtMiddlewares !== []) {
            return $this->builtMiddlewares;
        }

        $builtMiddlewares = $this->middlewares;

        foreach ($builtMiddlewares as $index => $definition) {
            if (in_array($definition, $this->disabledMiddlewares, true)) {
                unset($builtMiddlewares[$index]);
            }
        }

        return $this->builtMiddlewares = $builtMiddlewares;
    }
}
