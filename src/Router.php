<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\Interfaces\DispatcherInterface;
use Yiisoft\Router\Interfaces\MatcherInterface;
use Yiisoft\Router\Interfaces\MiddlewareAwareInterface;
use Yiisoft\Router\Interfaces\RouteCollectionInterface;
use Yiisoft\Router\Interfaces\RouteInterface;
use Yiisoft\Router\Interfaces\RouterInterface;

class Router implements RouterInterface, RouteCollectionInterface
{
    use MiddlewareAwareTrait;

    private RouteCollectionInterface $routeCollection;
    private MatcherInterface $matcher;
    private DispatcherInterface $dispatcher;

    public function __construct(RouteCollectionInterface $routeCollection, MatcherInterface $matcher, DispatcherInterface $dispatcher)
    {
        $this->routeCollection = $routeCollection;
        $this->matcher = $matcher;
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcher(): DispatcherInterface
    {
        return $this->dispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $matchingResult = $this->match($request);
        if ($matchingResult->isSuccess()) {
            $this->dispatcher->middlewares(\array_merge($matchingResult->getRoute()->getMiddlewares(), $this->middlewares));
        }

        $this->dispatcher->handle($request);
    }

    public function match(ServerRequestInterface $request): MatchingResult
    {
        return $this->matchForCollection($this->routeCollection, $request);
    }

    public function matchForCollection(RouteCollectionInterface $collection, ServerRequestInterface $request): MatchingResult
    {
        return $this->matcher->matchForCollection($collection, $request);
    }

    public function addRoute(RouteInterface $route): RouteCollectionInterface
    {
        // TODO: Implement addRoute() method.
    }

    public function addRoutes(array $routes): RouteCollectionInterface
    {
        // TODO: Implement addRoutes() method.
    }

    public function addCollection(RouteCollectionInterface $collection): RouteCollectionInterface
    {
        // TODO: Implement addCollection() method.
    }
}
