<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\Dispatcher\DispatcherInterface;
use Yiisoft\Router\Handler\HandlerAwareTrait;
use Yiisoft\Router\Route\RouteCollectionInterface;
use Yiisoft\Router\Route\RouteInterface;

use function array_merge;

final class Router implements RouterInterface, RouteCollectionInterface
{
    use HandlerAwareTrait;

    private RouteCollectionInterface $routeCollection;
    private MatcherInterface $matcher;
    private DispatcherInterface $dispatcher;

    public function __construct(RouteCollectionInterface $routeCollection, MatcherInterface $matcher, DispatcherInterface $dispatcher)
    {
        $this->routeCollection = clone $routeCollection;
        $this->matcher = $matcher;
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcher(): DispatcherInterface
    {
        return $this->dispatcher;
    }

    public function withDispatcher(DispatcherInterface $dispatcher): self
    {
        $new = clone $this;
        $new->dispatcher = $dispatcher;
        return $new;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $matchingResult = $this->match($request);
        if ($matchingResult->isSuccess()) {
            $route = $matchingResult->getRoute();
            $routeDispatcher = $route->getDispatcher();
            $handlers = array_merge($route->getHandlers(), $this->getHandlers());

            if ($routeDispatcher !== null) {
                return $routeDispatcher->handlers($handlers)->handle($request);
            }

            $this->dispatcher = $this->dispatcher->handlers($handlers);
        }

        return $this->dispatcher->handle($request);
    }

    public function match(ServerRequestInterface $request): MatchingResult
    {
        return $this->matchForRoutes($this->routeCollection->getRoutes(), $request);
    }

    public function matchForRoutes(iterable $routes, ServerRequestInterface $request): MatchingResult
    {
        return $this->matcher->matchForRoutes($routes, $request);
    }

    public function addRoute(RouteInterface $route): self
    {
        $this->routeCollection = $this->routeCollection->addRoute($route);

        return $this;
    }

    public function addRoutes(array $routes): self
    {
        // TODO: Implement addRoutes() method.
    }

    public function addCollection(RouteCollectionInterface $collection): self
    {
        // TODO: Implement addCollection() method.
    }

    public function getRoutes(): iterable
    {
        return $this->routeCollection->getRoutes();
    }
}
