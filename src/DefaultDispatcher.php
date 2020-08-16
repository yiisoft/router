<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Router\Interfaces\DispatcherInterface;

use function array_shift;

class DefaultDispatcher implements DispatcherInterface
{
    use MiddlewareAwareTrait;

    private ?ContainerInterface $container;

    public function __construct(?ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: validate middlewares before preparing
        $middleware = $this->shiftMiddleware();

        return $middleware->process($request, $this);
    }

    private function shiftMiddleware(): MiddlewareInterface
    {
        $middleware = array_shift($this->middlewares);
        if ($middleware === null) {
            throw new \Exception('There must be at least one middleware.');
        }

        return $this->prepareMiddleware($middleware);
    }

    private function prepareMiddleware($middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if (\is_string($middleware)) {
            if ($this->container === null) {
                throw new \InvalidArgumentException('Route container must not be null for lazy loaded middleware.');
            }
            return $this->container->get($middleware);
        }

        if (\is_array($middleware) && !\is_object($middleware[0])) {
            if ($this->container === null) {
                throw new \InvalidArgumentException('Route container must not be null for handler action.');
            }
            return $this->wrapCallable($middleware);
        }

        if ($this->isCallable($middleware)) {
            if ($this->container === null) {
                throw new \InvalidArgumentException('Route container must not be null for callable.');
            }
            return $this->wrapCallable($middleware);
        }

        return $middleware;
    }

    private function isCallable($definition): bool
    {
        if (\is_callable($definition)) {
            return true;
        }

        // This additional check is done for PHP 8, as callable types were changed
        // @see https://wiki.php.net/rfc/consistent_callables
        return \is_array($definition) && \array_keys($definition) === [0, 1] && \in_array($definition[1], \get_class_methods($definition[0]) ?? [], true);
    }

    private function wrapCallable($callback)
    {
        if (\is_array($callback) && !\is_object($callback[0])) {
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
}
