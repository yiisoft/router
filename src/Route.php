<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
final class Route implements RouteInterface, RouteParametersInterface
{
    private ?string $name = null;
    /** @var string[] */
    private array $methods;
    private string $pattern;
    private ?string $host = null;
    private bool $override = false;
    private ?MiddlewareDispatcher $dispatcher;
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

    /**
     * @return self
     */
    public function withDispatcher(MiddlewareDispatcher $dispatcher): RouteInterface
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
            if (in_array($definition, $this->disabledMiddlewareDefinitions, true)) {
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
     * @return RouteInterface
     */
    public static function get(string $pattern, ?MiddlewareDispatcher $dispatcher = null): RouteInterface
    {
        return self::methods([Method::GET], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return RouteInterface
     */
    public static function post(string $pattern, ?MiddlewareDispatcher $dispatcher = null): RouteInterface
    {
        return self::methods([Method::POST], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return RouteInterface
     */
    public static function put(string $pattern, ?MiddlewareDispatcher $dispatcher = null): RouteInterface
    {
        return self::methods([Method::PUT], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return RouteInterface
     */
    public static function delete(string $pattern, ?MiddlewareDispatcher $dispatcher = null): RouteInterface
    {
        return self::methods([Method::DELETE], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return RouteInterface
     */
    public static function patch(string $pattern, ?MiddlewareDispatcher $dispatcher = null): RouteInterface
    {
        return self::methods([Method::PATCH], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return RouteInterface
     */
    public static function head(string $pattern, ?MiddlewareDispatcher $dispatcher = null): RouteInterface
    {
        return self::methods([Method::HEAD], $pattern, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return RouteInterface
     */
    public static function options(string $pattern, ?MiddlewareDispatcher $dispatcher = null): RouteInterface
    {
        return self::methods([Method::OPTIONS], $pattern, $dispatcher);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param MiddlewareDispatcher|null $dispatcher
     *
     * @return RouteInterface
     */
    public static function methods(
        array $methods,
        string $pattern,
        ?MiddlewareDispatcher $dispatcher = null
    ): RouteInterface {
        $route = new self($dispatcher);
        $route->methods = $methods;
        $route->pattern = $pattern;

        return $route;
    }

    /**
     * @return self
     */
    public function name(string $name): RouteInterface
    {
        $route = clone $this;
        $route->name = $name;
        return $route;
    }

    /**
     * @return self
     */
    public function pattern(string $pattern): RouteInterface
    {
        $new = clone $this;
        $new->pattern = $pattern;
        return $new;
    }

    /**
     * @return self
     */
    public function host(string $host): RouteInterface
    {
        $route = clone $this;
        $route->host = rtrim($host, '/');
        return $route;
    }

    /**
     * @return self
     */
    public function override(): RouteInterface
    {
        $route = clone $this;
        $route->override = true;
        return $route;
    }

    /**
     * @return self
     */
    public function defaults(array $defaults): RouteInterface
    {
        $route = clone $this;
        $route->defaults = $defaults;
        return $route;
    }

    /**
     * @return self
     */
    public function middleware($middlewareDefinition): RouteInterface
    {
        if ($this->actionAdded) {
            throw new RuntimeException('middleware() can not be used after action().');
        }
        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    /**
     * @return self
     */
    public function prependMiddleware($middlewareDefinition): RouteInterface
    {
        if (!$this->actionAdded) {
            throw new RuntimeException('prependMiddleware() can not be used before action().');
        }
        $route = clone $this;
        array_unshift($route->middlewareDefinitions, $middlewareDefinition);
        return $route;
    }

    /**
     * @return self
     */
    public function action($middlewareDefinition): RouteInterface
    {
        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
        $route->actionAdded = true;
        return $route;
    }

    /**
     * @return self
     */
    public function disableMiddleware($middlewareDefinition): RouteInterface
    {
        $route = clone $this;
        $route->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    /**
     * @return self
     */
    public function preFlight(): RouteInterface
    {
        $route = clone $this;
        if (!in_array(Method::OPTIONS, $this->methods, true)) {
            $route->methods[] = Method::OPTIONS;
        }
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

    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
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
}
