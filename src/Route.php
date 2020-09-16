<?php

declare(strict_types=1);

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
    private array $middlewares = [];
    private array $defaults = [];

    private function __construct(?DispatcherInterface $dispatcher = null)
    {
        $this->dispatcher = $dispatcher;
    }

    public function withDispatcher(DispatcherInterface $dispatcher): self
    {
        $route = clone $this;
        $route->dispatcher = $dispatcher;
        return $route;
    }

    public function getDispatcherWithMiddlewares(): DispatcherInterface
    {
        return $this->dispatcher->withMiddlewares($this->middlewares);
    }

    public function hasDispatcher(): bool
    {
        return $this->dispatcher !== null;
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function get(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::GET], $pattern, $middleware, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function post(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::POST], $pattern, $middleware, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function put(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::PUT], $pattern, $middleware, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function delete(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::DELETE], $pattern, $middleware, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function patch(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::PATCH], $pattern, $middleware, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function head(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::HEAD], $pattern, $middleware, $dispatcher);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function options(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods([Method::OPTIONS], $pattern, $middleware, $dispatcher);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function methods(
        array $methods,
        string $pattern,
        $middleware = null,
        ?DispatcherInterface $dispatcher = null
    ): self {
        $route = new self($dispatcher);
        $route->methods = $methods;
        $route->pattern = $pattern;
        if ($middleware !== null) {
            $route->validateMiddleware($middleware);
            $route->middlewares[] = $middleware;
        }
        return $route;
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param DispatcherInterface|null $dispatcher
     * @return self
     */
    public static function anyMethod(string $pattern, $middleware = null, ?DispatcherInterface $dispatcher = null): self
    {
        return self::methods(Method::ANY, $pattern, $middleware, $dispatcher);
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
     * @param callable|string|array $middleware
     */
    private function validateMiddleware($middleware): void
    {
        if (
            is_string($middleware) && is_subclass_of($middleware, MiddlewareInterface::class)
        ) {
            return;
        }

        if ($this->isCallable($middleware) && (!is_array($middleware) || !is_object($middleware[0]))) {
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
     * @param callable|string|array $middleware
     * @return Route
     */
    public function addMiddleware($middleware): self
    {
        $this->validateMiddleware($middleware);

        $route = clone $this;
        $route->middlewares[] = $middleware;
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
