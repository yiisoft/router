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
use Yiisoft\Router\MethodFailureHandler\MethodFailureHandler;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private readonly MiddlewareDispatcher $dispatcher;
    private readonly MethodFailureHandler|null $methodFailureHandler;

    public function __construct(
        private readonly UrlMatcherInterface $matcher,
        ResponseFactoryInterface $responseFactory,
        MiddlewareFactory $middlewareFactory,
        private readonly CurrentRoute $currentRoute,
        ?EventDispatcherInterface $eventDispatcher = null,
        MethodFailureHandler|false|null $methodFailureHandler = null,
    ) {
        $this->dispatcher = new MiddlewareDispatcher($middlewareFactory, $eventDispatcher);
            $this->methodFailureHandler = $methodFailureHandler === false
                ? null
                : $methodFailureHandler ?? new MethodFailureHandler($responseFactory);

    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        $this->currentRoute->setUri($request->getUri());

        if ($result->isMethodFailure() && $this->methodFailureHandler !== null) {
            return $this->methodFailureHandler->handle($request, $result->methods());
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        $this->currentRoute->setRouteWithArguments($result->route(), $result->arguments());

        return $this->dispatcher
            ->withMiddlewares($result->route()->getData('enabledMiddlewares'))
            ->dispatch($request, $handler);
    }
}
