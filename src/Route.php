<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Http\Method;
use Yiisoft\Injector\Injector;
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
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;

    /**
     * @var callable[]|string[]|array[]
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
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface $container|null
     * @return self
     */
    public static function get(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods([Method::GET], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function post(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods([Method::POST], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function put(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods([Method::PUT], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function delete(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods([Method::DELETE], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function patch(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods([Method::PATCH], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function head(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods([Method::HEAD], $pattern, $middleware, $container);
    }

    /**
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function options(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods([Method::OPTIONS], $pattern, $middleware, $container);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param callable|string|array|null $middleware primary route handler {@see addMiddleware()}
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function methods(array $methods, string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        $route = new self($container);
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
     * @param ContainerInterface|null $container
     * @return self
     */
    public static function anyMethod(string $pattern, $middleware = null, ?ContainerInterface $container = null): self
    {
        return self::methods(Method::ANY, $pattern, $middleware, $container);
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
     * @param callable|string|array $middleware
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
            return $this->wrapCallable($middleware);
        }

        if ($this->isCallable($middleware)) {
            if ($this->container === null) {
                throw new InvalidArgumentException('Route container must not be null for callable.');
            }
            return $this->wrapCallable($middleware);
        }

        return $middleware;
    }

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        return is_array($definition) && array_keys($definition) === [0, 1] && in_array($definition[1], get_class_methods($definition[0]) ?? [], true);
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
            foreach ($this->middlewares as $middleware) {
                $handler = $this->wrap($this->prepareMiddleware($middleware), $handler);
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

    private function wrapCallable($callback): MiddlewareInterface
    {
        if (is_array($callback) && !is_object($callback[0])) {
            [$controller, $action] = $callback;
            return new class($controller, $action, $this->container) implements MiddlewareInterface {
                private string $class;
                private string $method;
                private ContainerInterface $container;

                public function __construct(string $class, string $method, ContainerInterface $container)
                {
                    $this->class = $class;
                    $this->method = $method;
                    $this->container = $container;
                }

                public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
                {
                    $controller = $this->container->get($this->class);
                    $response = (new Injector($this->container))->invoke([$controller, $this->method], [$request, $handler]);
                    if (is_string($response)) {
                        $wrappedResponse = $this->container->get(ResponseFactoryInterface::class)->createResponse();
                        $wrappedResponse->getBody()->write($response);

                        return $wrappedResponse;
                    }

                    return $response;
                }
            };
        }

        return new class($callback, $this->container) implements MiddlewareInterface {
            private ContainerInterface $container;
            private $callback;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
                if (is_string($response)) {
                    $wrappedResponse = $this->container->get(ResponseFactoryInterface::class)->createResponse();
                    $wrappedResponse->getBody()->write($response);

                    return $wrappedResponse;
                }
                return $response instanceof MiddlewareInterface ? $response->process($request, $handler) : $response;
            }
        };
    }
}
