<?php

namespace Yiisoft\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;

final class MatchingResult implements MiddlewareInterface
{
    private bool $success;
    private Route $route;
    private array $parameters = [];
    private array $methods = [];
    private ?ContainerInterface $container = null;

    private function __construct()
    {
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public static function fromSuccess(Route $route, array $parameters): self
    {
        $new = new self();
        $new->success = true;
        $new->route = $route;
        $new->parameters = $parameters;
        return $new;
    }

    public static function fromFailure(array $methods): self
    {
        $new = new self();
        $new->methods = $methods;
        $new->success = false;
        return $new;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isMethodFailure(): bool
    {
        return !$this->success && $this->methods !== Method::ANY;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->success === false) {
            return $handler->handle($request);
        }
        $route = $this->route;
        if ($this->container !== null && !$route->hasContainer()) {
            $route = $route->withContainer($this->container);
        }

        return $route->process($request, $handler);
    }
}
