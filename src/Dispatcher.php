<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

final class Dispatcher implements DispatcherInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     * @var RequestHandlerInterface|null stack of middleware
     */
    private ?RequestHandlerInterface $stack = null;

    private ContainerInterface $container;
    /**
     * @var callable[]|string[]|array[]
     */
    private array $middlewares = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $fallbackHandler): ResponseInterface
    {
        $handler = $fallbackHandler;
        if ($this->stack === null) {
            foreach ($this->middlewares as $middleware) {
                $handler = $this->wrap($this->prepareMiddleware($middleware), $handler);
            }
            $this->stack = $handler;
        }

        return $this->stack->handle($request);
    }

    public function withMiddlewares(array $middlewares): DispatcherInterface
    {
        $stack = $this->stack;
        $this->stack = null;
        $clone = clone $this;
        $clone->middlewares = $middlewares;
        $this->stack = $stack;

        return $clone;
    }

    public function hasMiddlewares(): bool
    {
        return $this->middlewares !== [];
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
                    return (new Injector($this->container))->invoke([$controller, $this->method], [$request, $handler]);
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
                return $response instanceof MiddlewareInterface ? $response->process($request, $handler) : $response;
            }
        };
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
}
