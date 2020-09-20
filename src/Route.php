<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Http\Method;

/**
 * Route defines a mapping from URL to callback / name and vice versa
 */
final class Route
{
    private ?string $name = null;
    /** @var string[] */
    private array $methods;
    private string $pattern;
    private ?string $host = null;
    private ?DispatcherInterface $dispatcher = null;

    /**
     * @var callable[]|string[]|array[]
     */
    private array $middlewareDefinitions = [];
    private array $defaults = [];

    private function __construct(?DispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
    }

    public function injectDispatcher(DispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcherWithMiddlewares(): DispatcherInterface
    {
        if ($this->dispatcher->hasMiddlewares()) {
            return $this->dispatcher;
        }

        return $this->dispatcher = $this->dispatcher->withMiddlewares($this->middlewareDefinitions);
    }

    public function hasDispatcher(): bool
    {
        return $this->dispatcher !== null;
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function get(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::GET], $pattern, $middlewareDefinition, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function post(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::POST], $pattern, $middlewareDefinition, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function put(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::PUT], $pattern, $middlewareDefinition, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function delete(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::DELETE], $pattern, $middlewareDefinition, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function patch(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::PATCH], $pattern, $middlewareDefinition, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function head(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::HEAD], $pattern, $middlewareDefinition, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function options(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::OPTIONS], $pattern, $middlewareDefinition, $dispatcher);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function methods(
        array $methods,
        string $pattern,
        $middlewareDefinition = null,
        ?DispatcherInterface $dispatcher = null
    ): self {
        $route = new self($dispatcher);
        $route->methods = $methods;
        $route->pattern = $pattern;
        if ($middlewareDefinition !== null) {
            $route->validateMiddleware($middlewareDefinition);
            $route->middlewareDefinitions[] = $middlewareDefinition;
        }
        return $route;
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middlewareDefinition primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function anyMethod(string $pattern, $middlewareDefinition = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods(Method::ANY, $pattern, $middlewareDefinition, $dispatcher);
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
     * Parameter default values indexed by parameter names
     *
     * @param array $defaults
     * @return self
     */
    public function defaults(array $defaults): self
    {
        $route = clone $this;
        $route->defaults = $defaults;
        return $route;
    }

    /**
     * @param callable|string|array $middlewareDefinition
     */
    private function validateMiddleware($middlewareDefinition): void
    {
        if (
            is_string($middlewareDefinition) && is_subclass_of($middlewareDefinition, MiddlewareInterface::class)
        ) {
            return;
        }

        if ($this->isCallable($middlewareDefinition) && (!is_array($middlewareDefinition) || !is_object($middlewareDefinition[0]))) {
            return;
        }

        throw new InvalidArgumentException('Parameter should be either PSR middleware class name or a callable.');
    }


    /**
     * Prepends a handler that should be invoked for a matching route.
     * Last added handler will be invoked first.
     *
     * Parameter can be a PSR middleware class name, handler action
     * (an array of [handlerClass, handlerMethod]) or a callable.
     *
     * For handler action and callable typed parameters are automatically injected using dependency
     * injection container passed to the route. Current request and handler could be obtained by
     * type-hinting for ServerRequestInterface and RequestHandlerInterface.
     *
     * @param callable|string|array $middlewareDefinition
     * @return Route
     */
    public function addMiddleware($middlewareDefinition): self
    {
        $this->validateMiddleware($middlewareDefinition);

        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
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

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        return is_array($definition) && array_keys($definition) === [0, 1] && in_array($definition[1], get_class_methods($definition[0]) ?? [], true);
    }
}
