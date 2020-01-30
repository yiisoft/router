<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use LogicException;
use Yiisoft\Http\Method;
use Yiisoft\Router\Middleware\Callback;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route defines a mapping from URL to callback / name and vice versa
 */
final class Route implements MiddlewareInterface, RequestHandlerInterface
{
    private ?string $name = null;
    /** @var string[] */
    private array $methods;
    private string $pattern;
    private ?string $host = null;
    /**
     * @var MiddlewareInterface[]|callable[]
     */
    private array $middlewares = [];
    private array $defaults = [];
    private RequestHandlerInterface $nextHandler;

    private function __construct()
    {
    }

    public static function get(string $pattern): self
    {
        $route = new static();
        $route->methods = [Method::GET];
        $route->pattern = $pattern;
        return $route;
    }

    public static function post(string $pattern): self
    {
        $route = new static();
        $route->methods = [Method::POST];
        $route->pattern = $pattern;
        return $route;
    }

    public static function put(string $pattern): self
    {
        $route = new static();
        $route->methods = [Method::PUT];
        $route->pattern = $pattern;
        return $route;
    }

    public static function delete(string $pattern): self
    {
        $route = new static();
        $route->methods = [Method::DELETE];
        $route->pattern = $pattern;
        return $route;
    }

    public static function patch(string $pattern): self
    {
        $route = new static();
        $route->methods = [Method::PATCH];
        $route->pattern = $pattern;
        return $route;
    }

    public static function head(string $pattern): self
    {
        $route = new static();
        $route->methods = [Method::HEAD];
        $route->pattern = $pattern;
        return $route;
    }

    public static function options(string $pattern): self
    {
        $route = new static();
        $route->methods = [Method::OPTIONS];
        $route->pattern = $pattern;
        return $route;
    }

    public static function methods(array $methods, string $pattern): self
    {
        $route = new static();
        $route->methods = $methods;
        $route->pattern = $pattern;
        return $route;
    }

    public static function anyMethod(string $pattern): self
    {
        $route = new static();
        $route->methods = Method::ANY;
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
     * Parameter default values indexed by parameter names
     *
     * @param array $defaults
     * @return Route
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
     * Adds a handler that should be invoked for a matching route.
     * It can be either a PSR middleware or a callable with the following signature:
     *
     * ```
     * function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
     * ```
     *
     * @param MiddlewareInterface|callable $middleware
     * @return Route
     */
    public function to($middleware): self
    {
        $route = clone $this;
        $route->middlewares[] = $this->prepareMiddleware($middleware);
        return $route;
    }

    /**
     * Adds a handler that should be invoked for a matching route.
     * It can be either a PSR middleware or a callable with the following signature:
     *
     * ```
     * function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
     * ```
     *
     * @param MiddlewareInterface|callable $middleware
     * @return Route
     */
    public function then($middleware): self
    {
        return $this->to($middleware);
    }

    /**
     * Prepends a handler that should be invoked for a matching route.
     * It can be either a PSR middleware or a callable with the following signature:
     *
     * ```
     * function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
     * ```
     *
     * @param MiddlewareInterface|callable $middleware
     * @return Route
     */
    public function prepend($middleware): self
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

    /**
     * @internal please use {@see dispatch()} or {@see process()}
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->middlewares);
        next($this->middlewares);
        if ($middleware === false) {
            if ($this->nextHandler !== null) {
                return $this->nextHandler->handle($request);
            }

            throw new LogicException('Middleware stack exhausted');
        }

        return $middleware->process($request, $this);
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        reset($this->middlewares);
        return $this->handle($request);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $nextHandler): ResponseInterface
    {
        $this->nextHandler = $nextHandler;
        return $this->dispatch($request);
    }
}
