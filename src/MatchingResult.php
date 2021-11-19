<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

final class MatchingResult implements MiddlewareInterface
{
    private bool $success;
    private Route $route;
    private array $arguments = [];
    private array $methods = [];
    private ?MiddlewareDispatcher $dispatcher = null;

    private function __construct()
    {
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $new = clone $this;
        $new->dispatcher = $dispatcher;
        return $new;
    }

    public static function fromSuccess(Route $route, array $arguments): self
    {
        $new = new self();
        $new->success = true;
        $new->route = $route;
        $new->arguments = $arguments;
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
        return !$this->success && $this->methods !== Method::ALL;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function route(): Route
    {
        return $this->route;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isSuccess()) {
            return $handler->handle($request);
        }
        $route = $this->route;

        if ($this->dispatcher !== null && !$route->hasDispatcher()) {
            $route->injectDispatcher($this->dispatcher);
        }

        return $route->getDispatcherWithMiddlewares()->dispatch($request, $handler);
    }
}
