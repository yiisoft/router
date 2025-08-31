<?php

declare(strict_types=1);

namespace Yiisoft\Router\Middleware;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\MethodFailureHandlerInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private readonly MiddlewareDispatcher $dispatcher;
    private bool $ignoreMethodFailureHandler = false;

    public function __construct(
        private readonly UrlMatcherInterface $matcher,
        private readonly ResponseFactoryInterface $responseFactory,
        MiddlewareFactory $middlewareFactory,
        private readonly CurrentRoute $currentRoute,
        ?EventDispatcherInterface $eventDispatcher = null,
        private readonly ?MethodFailureHandlerInterface $allowedMethodsHandler = null,
        private readonly ?MethodFailureHandlerInterface $methodNotAllowedHandler = null
    ) {
        $this->dispatcher = new MiddlewareDispatcher($middlewareFactory, $eventDispatcher);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        $this->currentRoute->setUri($request->getUri());

        if (!$this->ignoreMethodFailureHandler && $result->isMethodFailure()) {
            return $request->getMethod() === Method::OPTIONS
                    ? $this->getAllowedMethodsResponse($request, $result->methods())
                    : $this->getMethodNotAllowedResponse($request, $result->methods());
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

    /**
     * @param string[] $methods
     */
    private function getAllowedMethodsResponse(ServerRequestInterface $request, array $methods): ResponseInterface
    {
        return $this->allowedMethodsHandler !== null
                ? $this->allowedMethodsHandler
                    ->withAllowedMethods($methods)
                    ->handle($request)
                : $this->responseFactory
                    ->createResponse(Status::NO_CONTENT)
                    ->withHeader(Header::ALLOW, implode(', ', $methods));
    }

    /**
     * @param string[] $methods
     */
    private function getMethodNotAllowedResponse(ServerRequestInterface $request, array $methods): ResponseInterface
    {
        return $this->methodNotAllowedHandler !== null
                ? $this->methodNotAllowedHandler
                    ->withAllowedMethods($methods)
                    ->handle($request)
                : $this->responseFactory
                    ->createResponse(Status::METHOD_NOT_ALLOWED)
                    ->withHeader(Header::ALLOW, implode(', ', $methods));
    }
}
