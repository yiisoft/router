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
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route implements Stringable
{
    private bool $actionAdded = false;
    /**
     * @var array[]|callable[]|string[]
     */
    private array $builtMiddlewares = [];
    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewares = [];
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
     * @param array<string,scalar|Stringable|null> $defaults Parameter default values indexed by parameter names.
     * @param bool $override Marks route as override. When added it will replace existing route with the same name.
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function __construct(
        array $methods,
        private string $pattern,
        private ?string $name = null,
        array|callable|string $action = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        private bool $override = false,
        private array $disabledMiddlewares = [],
    ) {
        $this->assertListOfStrings($methods, 'methods');
        $this->assertMiddlewares($middlewares);
        $this->assertListOfStrings($hosts, 'hosts');
        $this->methods = $methods;
        $this->middlewares = $middlewares;
        $this->hosts = $hosts;
        $this->defaults = array_map('\strval', $defaults);
        if (!empty($action)) {
            $this->middlewares[] = $action;
            $this->actionAdded = true;
        }
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
        return new self(
            methods: $methods,
            pattern: $pattern
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
     *                           (T is 'builtMiddlewares' ? array<array-key,array|callable|string> : mixed)
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
            'name' => $this->name ??
                (implode(', ', $this->methods) . ' ' . implode('|', $this->hosts) . $this->pattern),
            'pattern' => $this->pattern,
            'host' => $this->hosts[0] ?? null,
            'hosts' => $this->hosts,
            'methods' => $this->methods,
            'defaults' => $this->defaults,
            'override' => $this->override,
            'hasMiddlewares' => !empty($this->middlewares),
            'builtMiddlewares' => $this->getBuiltMiddlewares(),
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
     * @return array[]|callable[]|string[]
     */
    private function getBuiltMiddlewares(): array
    {
        // Don't build middlewares if we did it earlier.
        // This improves performance in event-loop applications.
        if (!empty($this->builtMiddlewares)) {
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

    /**
     * @psalm-assert array<string> $items
     */
    private function assertListOfStrings(array $items, string $argument): void
    {
        foreach ($items as $item) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException('Invalid $' . $argument . ' provided, list of string expected.');
            }
        }
    }

    /**
     * @psalm-assert array<array|callable|string> $middlewares
     */
    private function assertMiddlewares(array $middlewares): void
    {
        /** @var mixed $middleware */
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                continue;
            }

            if (is_callable($middleware) || is_array($middleware)) {
                continue;
            }

            throw new \InvalidArgumentException(
                'Invalid $middlewares provided, list of string or array or callable expected.'
            );
        }
    }
}
