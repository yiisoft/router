<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Yiisoft\Http\Method;
use Yiisoft\Router\Middleware\Callback;
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
    /**
     * Contains a chain of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;

    /**
     * @var MiddlewareInterface[]|callable[]
     */
    private array $middlewares = [];
    private array $defaults = [];

    private function __construct()
    {
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function get(string $pattern, $middleware = null): self
    {
        return static::methods([Method::GET], $pattern, $middleware);
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function post(string $pattern, $middleware = null): self
    {
        return static::methods([Method::POST], $pattern, $middleware);
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function put(string $pattern, $middleware = null): self
    {
        return static::methods([Method::PUT], $pattern, $middleware);
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function delete(string $pattern, $middleware = null): self
    {
        return static::methods([Method::DELETE], $pattern, $middleware);
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function patch(string $pattern, $middleware = null): self
    {
        return static::methods([Method::PATCH], $pattern, $middleware);
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function head(string $pattern, $middleware = null): self
    {
        return static::methods([Method::HEAD], $pattern, $middleware);
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function options(string $pattern, $middleware = null): self
    {
        return static::methods([Method::OPTIONS], $pattern, $middleware);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function methods(array $methods, string $pattern, $middleware = null): self
    {
        $route = new static();
        $route->methods = $methods;
        $route->pattern = $pattern;
        if ($middleware !== null) {
            $route->middlewares[] = $route->prepareMiddleware($middleware);
        }
        return $route;
    }

    /**
     * @param string $pattern
     * @param callable|MiddlewareInterface|null $middleware
     * @return static
     */
    public static function anyMethod(string $pattern, $middleware = null): self
    {
        return static::methods(Method::ANY, $pattern, $middleware);
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
     * @param callable|MiddlewareInterface $middleware
     * @return MiddlewareInterface
     */
    private function prepareMiddleware($middleware): MiddlewareInterface
    {
        if (is_callable($middleware)) {
            $middleware = new Callback($middleware);
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException('Parameter should be either a PSR middleware or a callable.');
        }

        return $middleware;
    }

    /**
     * Prepends a handler that should be invoked for a matching route.
     * Last added handler will be invoked first.
     * It can be either a PSR middleware or a callable with the following signature:
     *
     * ```
     * function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
     * ```
     *     * Last added middleware will be invoked first.
     *
     * @param MiddlewareInterface|callable $middleware
     * @return Route
     */
    public function addMiddleware($middleware): self
    {
        $route = clone $this;
        array_unshift($route->middlewares, $this->prepareMiddleware($middleware));
        return $route;
    }

    public function __toString()
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
                $handler = $this->wrap($this->middlewares[$i], $handler);
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
