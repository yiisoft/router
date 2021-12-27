<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
final class Route
{
    private ?string $name = null;

    /**
     * @var string[]
     */
    private array $methods;

    private string $pattern;
    private ?string $host = null;
    private bool $override = false;
    private ?MiddlewareDispatcher $dispatcher;
    private bool $actionAdded = false;

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    private array $disabledMiddlewareDefinitions = [];
    private array $defaults = [];

    /**
     * @param string[] $methods
     */
    private function __construct(array $methods, string $pattern, ?MiddlewareDispatcher $dispatcher = null)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @psalm-assert MiddlewareDispatcher $this->dispatcher
     */
    public function injectDispatcher(MiddlewareDispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return self
     */
    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $route = clone $this;
        $route->dispatcher = $dispatcher;
        return $route;
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function get(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods([Method::GET], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function post(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods([Method::POST], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function put(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods([Method::PUT], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function delete(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods([Method::DELETE], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function patch(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods([Method::PATCH], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function head(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods([Method::HEAD], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function options(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods([Method::OPTIONS], $pattern, $dispatcher);
    }

    /**
     * @param string[] $methods
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function methods(
        array $methods,
        string $pattern,
        ?MiddlewareDispatcher $dispatcher = null
    ): self {
        return new self($methods, $pattern, $dispatcher);
    }

    /**
     * @return self
     */
    public function name(string $name): self
    {
        $route = clone $this;
        $route->name = $name;
        return $route;
    }

    /**
     * @return self
     */
    public function pattern(string $pattern): self
    {
        $new = clone $this;
        $new->pattern = $pattern;
        return $new;
    }

    /**
     * @return self
     */
    public function host(string $host): self
    {
        $route = clone $this;
        $route->host = rtrim($host, '/');
        return $route;
    }

    /**
     * Marks route as override. When added it will replace existing route with the same name.
     *
     * @return self
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
     * @param array $defaults
     *
     * @return self
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
     *
     * @param array|callable|string $middlewareDefinition
     *
     * @return self
     */
    public function middleware($middlewareDefinition): self
    {
        if ($this->actionAdded) {
            throw new RuntimeException('middleware() can not be used after action().');
        }
        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * Last added handler will be executed first.
     *
     * @param array|callable|string $middlewareDefinition
     *
     * @return self
     */
    public function prependMiddleware($middlewareDefinition): self
    {
        if (!$this->actionAdded) {
            throw new RuntimeException('prependMiddleware() can not be used before action().');
        }
        $route = clone $this;
        array_unshift($route->middlewareDefinitions, $middlewareDefinition);
        return $route;
    }

    /**
     * Appends action handler. It is a primary middleware definition that should be invoked last for a matched route.
     *
     * @param array|callable|string $middlewareDefinition
     *
     * @return self
     */
    public function action($middlewareDefinition): self
    {
        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
        $route->actionAdded = true;
        return $route;
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
        $route = clone $this;
        $route->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @internal
     *
     * @psalm-template T as string
     * @psalm-param T $key
     * @psalm-return (
     *   T is ('name'|'pattern') ? string :
     *     (T is 'host' ? string|null :
     *       (T is 'methods' ? array<array-key,string> :
     *         (T is 'defaults' ? array :
     *           (T is ('override'|'hasMiddlewares'|'hasDispatcher') ? bool :
     *             (T is 'dispatcherWithMiddlewares' ? MiddlewareDispatcher : mixed)
     *           )
     *         )
     *       )
     *     )
     * )
     */
    public function getData(string $key)
    {
        switch ($key) {
            case 'name':
                return $this->name ??
                    (implode(', ', $this->methods) . ' ' . (string) $this->host . $this->pattern);
            case 'pattern':
                return $this->pattern;
            case 'host':
                return $this->host;
            case 'methods':
                return $this->methods;
            case 'defaults':
                return $this->defaults;
            case 'override':
                return $this->override;
            case 'dispatcherWithMiddlewares':
                return $this->getDispatcherWithMiddlewares();
            case 'hasMiddlewares':
                return $this->middlewareDefinitions !== [];
            case 'hasDispatcher':
                return $this->dispatcher !== null;
            default:
                throw new InvalidArgumentException('Unknown data key: ' . $key);
        }
    }

    public function __toString(): string
    {
        $result = '';

        if ($this->name !== null) {
            $result .= '[' . $this->name . '] ';
        }

        if ($this->methods !== []) {
            $result .= implode(',', $this->methods) . ' ';
        }
        if ($this->host !== null && strrpos($this->pattern, $this->host) === false) {
            $result .= $this->host;
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
            'host' => $this->host,
            'defaults' => $this->defaults,
            'override' => $this->override,
            'actionAdded' => $this->actionAdded,
            'middlewareDefinitions' => $this->middlewareDefinitions,
            'disabledMiddlewareDefinitions' => $this->disabledMiddlewareDefinitions,
            'middlewareDispatcher' => $this->dispatcher,
        ];
    }

    private function getDispatcherWithMiddlewares(): MiddlewareDispatcher
    {
        if ($this->dispatcher === null) {
            throw new RuntimeException(sprintf('There is no dispatcher in the route %s', $this->getData('name')));
        }

        // Don't add middlewares to dispatcher if we did it earlier.
        // This improves performance in event-loop applications.
        if ($this->dispatcher->hasMiddlewares()) {
            return $this->dispatcher;
        }

        /** @var mixed $definition */
        foreach ($this->middlewareDefinitions as $index => $definition) {
            if (in_array($definition, $this->disabledMiddlewareDefinitions, true)) {
                unset($this->middlewareDefinitions[$index]);
            }
        }

        return $this->dispatcher = $this->dispatcher->withMiddlewares($this->middlewareDefinitions);
    }
}
