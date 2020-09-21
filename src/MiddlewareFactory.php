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

final class MiddlewareFactory implements MiddlewareFactoryInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create($middlewareDefinition): MiddlewareInterface
    {
        return $this->createMiddleware($middlewareDefinition);
    }

    /**
     * @param callable|string|array $middlewareDefinition
     * @return MiddlewareInterface
     */
    private function createMiddleware($middlewareDefinition): MiddlewareInterface
    {
        $this->validateMiddleware($middlewareDefinition);

        if (is_string($middlewareDefinition)) {
            return $this->container->get($middlewareDefinition);
        }

        if (is_array($middlewareDefinition) && !is_object($middlewareDefinition[0])) {
            return $this->wrapCallable($middlewareDefinition);
        }

        if ($this->isCallable($middlewareDefinition)) {
            return $this->wrapCallable($middlewareDefinition);
        }

        throw new \RuntimeException('Middleware creating error.');
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

    private function isCallable($definition): bool
    {
        if (is_callable($definition)) {
            return true;
        }

        return is_array($definition)
            && array_keys($definition) === [0, 1]
            && in_array(
                $definition[1],
                class_exists($definition[0]) ? get_class_methods($definition[0]) : [],
                true
            );
    }
}
