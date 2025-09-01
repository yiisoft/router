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
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\AllowedMethodsHandler;
use Yiisoft\Router\MethodFailureHandlerInterface;
use Yiisoft\Router\MethodNotAllowedHandler;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private readonly MiddlewareDispatcher $dispatcher;
    private readonly ?MethodFailureHandlerInterface $allowedMethodsHandler;
    private readonly ?MethodFailureHandlerInterface $methodNotAllowedHandler;
    private bool $ignoreMethodFailureHandler = false;

    public function __construct(
        private readonly UrlMatcherInterface $matcher,
        ResponseFactoryInterface $responseFactory,
        MiddlewareFactory $middlewareFactory,
        private readonly CurrentRoute $currentRoute,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?MethodFailureHandlerInterface $allowedMethodsHandler = null,
        ?MethodFailureHandlerInterface $methodNotAllowedHandler = null
    ) {
        $this->dispatcher = new MiddlewareDispatcher($middlewareFactory, $eventDispatcher);
        $this->allowedMethodsHandler = $allowedMethodsHandler ?? new AllowedMethodsHandler($responseFactory);
        $this->methodNotAllowedHandler = $methodNotAllowedHandler ?? new MethodNotAllowedHandler($responseFactory);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        $this->currentRoute->setUri($request->getUri());

        if (!$this->ignoreMethodFailureHandler && $result->isMethodFailure()) {
            return $request->getMethod() === Method::OPTIONS
                    ? $this->allowedMethodsHandler
                        ->withAllowedMethods($result->methods())
                        ->handle($request)
                    : $this->methodNotAllowedHandler
                        ->withAllowedMethods($result->methods())
                        ->handle($request);
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        $this->currentRoute->setRouteWithArguments($result->route(), $result->arguments());

        return $this->dispatcher
            ->withMiddlewares($result->route()->getData('enabledMiddlewares'))
            ->dispatch($request, $handler);
    }

    public function ignoreMethodFailureHandler(): self
    {
        $new = clone $this;
        $new->ignoreMethodFailureHandler = true;
        return $new;
    }
}
