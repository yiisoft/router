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
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private readonly MiddlewareDispatcher $dispatcher;

    public function __construct(
        private readonly UrlMatcherInterface $matcher,
        private readonly ResponseFactoryInterface $responseFactory,
        MiddlewareFactory $middlewareFactory,
        private readonly CurrentRoute $currentRoute,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->dispatcher = new MiddlewareDispatcher($middlewareFactory, $eventDispatcher);
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

        return $this->dispatcher
            ->withMiddlewares($result->route()->getEnabledMiddlewares())
            ->dispatch($request, $handler);
    }
}
