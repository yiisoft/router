<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Yiisoft\Http\Method;
use Yiisoft\Router\Middleware\Callback;
use Yiisoft\Router\Middleware\ActionCaller;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Route defines a mapping from URL to callback / name and vice versa
 */
final class Route implements MiddlewareInterface
{
    private ?string $name = null;
    /** @var string[] */
    private array $methods;
    private string $pattern;
    private ?string $host = null;
    private ?ContainerInterface $container = null;
    /**
     * Contains a chain of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;

    /**
     * @var MiddlewareInterface[]|callable[]|string[]|array[]
     */
    private array $middlewares = [];
    private array $defaults = [];

    private function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function withContainer(ContainerInterface $container)
    {
        $route = clone $this;
        $route->container = $container;
        return $route;
    }

    public function hasContainer()
    {
        return $this->container !== null;
    }

    /**
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface $container|null
     * @return static
     */
    public static function get(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods([Method::GET], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function post(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods([Method::POST], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function put(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods([Method::PUT], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function delete(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods([Method::DELETE], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function patch(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods([Method::PATCH], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function head(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods([Method::HEAD], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function options(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods([Method::OPTIONS], $pattern, $middleware, $container);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function methods(array $methods, string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        $route = new static($container);
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
     * @param MiddlewareInterface|callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return static
     */
    public static function anyMethod(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return static::methods(Method::ANY, $pattern, $middleware, $container);
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
     * @param MiddlewareInterface|callable|string|array $middleware
     */
    private function validateMiddleware($middleware): void
    {
        if (
            is_string($middleware) && is_subclass_of($middleware, MiddlewareInterface::class)
        ) {
            return;
        }

        if (is_callable($middleware)) {
            return;
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException('Parameter should be either PSR middleware instance, PSR middleware class name, handler action or a callable.');
        }
    }

    /**
     * @param MiddlewareInterface|callable|string|array $middleware
     * @return MiddlewareInterface|string|array
     */
    private function prepareMiddleware($middleware)
    {
        if (is_string($middleware)) {
            if ($this->container === null) {
                throw new InvalidArgumentException('Route container must not be null for lazy loaded middleware.');
            }
            return $this->container->get($middleware);
        }

        if (is_array($middleware) && !is_object($middleware[0])) {
            if ($this->container === null) {
                throw new InvalidArgumentException('Route container must not be null for handler action.');
            }
            return new ActionCaller($middleware[0], $middleware[1], $this->container);
        }

        if (is_callable($middleware)) {
            if ($this->container === null) {
                throw new InvalidArgumentException('Route container must not be null for callable.');
            }
            return new Callback($middleware, $this->container);
        }

        return $middleware;
    }

    /**
     * Prepends a handler that should be invoked for a matching route.
     * Last added handler will be invoked first.
     *
     * Parameter should be either PSR middleware instance, PSR middleware class name, handler action or a callable.
     *
     * It can be a PSR middleware instance, PSR middleware class name, handler action
     * (an array of [handlerClass, handlerMethod]) or a callable.
     *
     * For handler action and callable typed parameters are automatically injected using dependency
     * injection container passed to the route. Current request and handler could be obtained by
     * type-hinting for ServerRequestInterface and RequestHandlerInterface.
     *
     * @param MiddlewareInterface|callable|string|array $middleware
     * @return Route
     */
    public function addMiddleware($middleware): self
    {
        $this->validateMiddleware($middleware);

        $route = clone $this;
        array_unshift($route->middlewares, $middleware);
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
        return $this->name ?? (implode(', ', $this->methods) . ' ' . $this->pattern);
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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->stack === null) {
            for ($i = count($this->middlewares) - 1; $i >= 0; $i--) {
                $handler = $this->wrap($this->prepareMiddleware($this->middlewares[$i]), $handler);
            }
            $this->stack = $handler;
        }

        return $this->stack->handle($request);
    }

    /**
     * Wraps handler by middlewares
     */
    private function wrap(MiddlewareInterface $middleware, RequestHandlerInterface $handler): RequestHandlerInterface
    {
        return new class($middleware, $handler) implements RequestHandlerInterface {
            private MiddlewareInterface $middleware;
            private RequestHandlerInterface $handler;

            public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $handler)
            {
                $this->middleware = $middleware;
                $this->handler = $handler;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, $this->handler);
            }
        };
    }
}
