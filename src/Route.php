<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
final class Route
{
    private ?string $name = null;
    /** @var string[] */
    private array $methods;
    private string $pattern;
    private ?string $host = null;
    private bool $override = false;
    private ?MiddlewareDispatcher $dispatcher = null;
    private bool $actionAdded = false;
    private array $middlewareDefinitions = [];
    private array $disabledMiddlewareDefinitions = [];
    private array $defaults = [];

    private function __construct(?MiddlewareDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
    }

    public function injectDispatcher(MiddlewareDispatcher $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $route = clone $this;
        $route->dispatcher = $dispatcher;
        return $route;
    }

    public function getDispatcherWithMiddlewares(): MiddlewareDispatcher
    {
        if ($this->dispatcher->hasMiddlewares()) {
            return $this->dispatcher;
        }

        foreach ($this->middlewareDefinitions as $index => $definition) {
            if (in_array($definition, $this->disabledMiddlewareDefinitions)) {
                unset($this->middlewareDefinitions[$index]);
            }
        }

        return $this->dispatcher = $this->dispatcher->withMiddlewares($this->middlewareDefinitions);
    }

    public function hasDispatcher(): bool
    {
        return $this->dispatcher !== null;
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
     * @param array|callable|string|null $middlewareDefinition primary route handler {@see prependMiddleware()}
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
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return self
     */
    public static function anyMethod(string $pattern, ?MiddlewareDispatcher $dispatcher = null): self
    {
        return self::methods(Method::ALL, $pattern, $dispatcher);
    }

    /**
     * @param array $methods
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
        $route = new self($dispatcher);
        $route->methods = $methods;
        $route->pattern = $pattern;

        return $route;
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
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function middleware($middlewareDefinition): self
    {
        if ($this->actionAdded) {
            throw new RuntimeException('Method middleware() can\'t be used after action().');
        }
        $route = clone $this;
        array_unshift($route->middlewareDefinitions, $middlewareDefinition);
        return $route;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * Last added handler will be executed first.
     *
     * @param mixed $middlewareDefinition
     * @return $this
     */
    public function prependMiddleware($middlewareDefinition): self
    {
        if (!$this->actionAdded) {
            throw new RuntimeException('Method prependMiddleware() can\'t be used before action().');
        }
        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    public function action($middlewareDefinition): self
    {
        $route = clone $this;
        array_unshift($route->middlewareDefinitions, $middlewareDefinition);
        $route->actionAdded = true;
        return $route;
    }

    public function disableMiddleware($middlewareDefinition): self
    {
        $route = clone $this;
        $route->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $route;
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

    public function getName(): string
    {
        return $this->name ?? (implode(', ', $this->methods) . ' ' . $this->host . $this->pattern);
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function isOverride(): bool
    {
        return $this->override;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }
}
