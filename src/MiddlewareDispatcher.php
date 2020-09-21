<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher
{
    /**
     * Contains a stack of middleware handler.
     * @var MiddlewareStackInterface stack of middleware
     */
    private MiddlewareStackInterface $stack;

    private MiddlewareFactoryInterface $middlewareFactory;

    /**
     * @var callable[]|string[]|array[]
     */
    private array $middlewareDefinitions = [];

    public function __construct(MiddlewareFactoryInterface $middlewareFactory, MiddlewareStackInterface $stack)
    {
        $this->middlewareFactory = $middlewareFactory;
        $this->stack = $stack;
    }

    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $fallbackHandler): ResponseInterface
    {
        if ($this->stack->isEmpty()) {
            $this->stack = $this->stack->build($this->buildMiddlewares(), $fallbackHandler);
        }

        return $this->stack->handle($request);
    }

    public function withMiddlewares(array $middlewareDefinitions): MiddlewareDispatcher
    {
        $clone = clone $this;
        $clone->middlewareDefinitions = $middlewareDefinitions;
        $clone->stack->reset();

        return $clone;
    }

    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    private function buildMiddlewares(): array
    {
        $middlewares = [];
        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = $this->middlewareFactory->create($middlewareDefinition);
        }

        return $middlewares;
    }
}
