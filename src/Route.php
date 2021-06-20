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
    public const NAME = 'name';
    public const METHODS = 'methods';
    public const PATTERN = 'pattern';
    public const HOST = 'host';
    public const DEFAULTS = 'defaults';
    public const OVERRIDE = 'override';

    /** @var string[] */
    private array $parameters;
    private bool $actionAdded = false;
    private ?MiddlewareDispatcher $dispatcher;
    private array $middlewareDefinitions = [];
    private array $disabledMiddlewareDefinitions = [];

    private function __construct(?MiddlewareDispatcher $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
        // Set default parameters
        $this->parameters = [
            self::OVERRIDE => false,
            self::DEFAULTS => [],
        ];
    }

    private function setParameter(string $parameter, $value): void
    {
        $this->parameters[$parameter] = $value;
    }

    public function getParameter(string $parameter, $default = null)
    {
        return array_key_exists($parameter, $this->parameters) ? $this->parameters[$parameter] : $default;
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
        $route->setParameter(self::METHODS, $methods);
        $route->setParameter(self::PATTERN, $pattern);

        return $route;
    }

    public function name(string $name): self
    {
        $route = clone $this;
        $route->setParameter(self::NAME, $name);
        return $route;
    }

    public function pattern(string $pattern): self
    {
        $route = clone $this;
        $route->setParameter(self::PATTERN, $pattern);
        return $route;
    }

    public function host(string $host): self
    {
        $route = clone $this;
        $route->setParameter(self::HOST, rtrim($host, '/'));
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
        $route->setParameter(self::OVERRIDE, true);
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
        $route->setParameter(self::DEFAULTS, $defaults);
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
            throw new RuntimeException('middleware() can not be used after action().');
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
     *
     * @return self
     */
    public function prependMiddleware($middlewareDefinition): self
    {
        if (!$this->actionAdded) {
            throw new RuntimeException('prependMiddleware() can not be used before action().');
        }
        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    /**
     * Appends action handler. It is a primary middleware definition that should be invoked last for a matched route.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function action($middlewareDefinition): self
    {
        $route = clone $this;
        array_unshift($route->middlewareDefinitions, $middlewareDefinition);
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
     * @return $this
     */
    public function disableMiddleware($middlewareDefinition): self
    {
        $route = clone $this;
        $route->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    public function __toString(): string
    {
        $result = '';

        if ($this->getParameter(self::NAME) !== null) {
            $result .= '[' . $this->getParameter(self::NAME) . '] ';
        }

        if ($this->getParameter(self::METHODS) !== []) {
            $result .= implode(',', $this->getParameter(self::METHODS)) . ' ';
        }
        if ($this->getParameter(self::HOST) !== null && strrpos($this->getParameter(self::PATTERN), $this->getParameter(self::HOST)) === false) {
            $result .= $this->getParameter(self::HOST);
        }
        $result .= $this->getParameter(self::PATTERN);

        return $result;
    }

    public function getDefaultName(): string
    {
        return implode(', ', $this->getParameter(self::METHODS)) . ' ' . $this->getParameter(self::HOST) . $this->getParameter(self::PATTERN);
    }

    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }
}
