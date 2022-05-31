<?php

declare(strict_types=1);

namespace Yiisoft\Router\Middleware;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactoryInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private UrlMatcherInterface $matcher;
    private ResponseFactoryInterface $responseFactory;
    private MiddlewareDispatcher $dispatcher;
    private CurrentRoute $currentRoute;

    public function __construct(
        UrlMatcherInterface $matcher,
        ResponseFactoryInterface $responseFactory,
        MiddlewareFactoryInterface $middlewareFactory,
        CurrentRoute $currentRoute,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->matcher = $matcher;
        $this->responseFactory = $responseFactory;
        $this->dispatcher = new MiddlewareDispatcher($middlewareFactory, $eventDispatcher);
        $this->currentRoute = $currentRoute;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        $this->currentRoute->setUri($request->getUri());

        if ($result->isMethodFailure()) {
            if ($request->getMethod() === Method::OPTIONS) {
                return $this->responseFactory
                    ->createResponse(Status::NO_CONTENT)
                    ->withHeader('Allow', implode(', ', $result->methods()));
            }
            return $this->responseFactory
                ->createResponse(Status::METHOD_NOT_ALLOWED)
                ->withHeader('Allow', implode(', ', $result->methods()));
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        $this->currentRoute->setRouteWithArguments($result->route(), $result->arguments());

        return $result
            ->withDispatcher($this->dispatcher)
            ->process($request, $handler);
    }
}
