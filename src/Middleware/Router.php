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
use Yiisoft\Router\MethodsResponseFactoryInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private MiddlewareDispatcher $dispatcher;
    private bool $ignoreMethodFailureHandler = false;
    private ?MethodsResponseFactoryInterface $optionsResponseFactory = null;
    private ?MethodsResponseFactoryInterface $notAllowedResponseFactory = null;

    public function __construct(
        private UrlMatcherInterface $matcher,
        private ResponseFactoryInterface $responseFactory,
        MiddlewareFactory $middlewareFactory,
        private CurrentRoute $currentRoute,
        ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->dispatcher = new MiddlewareDispatcher($middlewareFactory, $eventDispatcher);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        $this->currentRoute->setUri($request->getUri());

        if (!$this->ignoreMethodFailureHandler && $result->isMethodFailure()) {
            return $request->getMethod() === Method::OPTIONS
                    ? $this->getOptionsResponse($request, $result->methods())
                    : $this->getMethodNotAllowedResponse($request, $result->methods());
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        $this->currentRoute->setRouteWithArguments($result->route(), $result->arguments());

        return $result
            ->withDispatcher($this->dispatcher)
            ->process($request, $handler);
    }

    public function ignoreMethodFailureHandler(): self
    {
        $new = clone $this;
        $new->ignoreMethodFailureHandler = true;
        return $new;
    }

    public function withOptionsResponseFactory(MethodsResponseFactoryInterface $optionsResponseFactory): self
    {
        $new = clone $this;
        $new->optionsResponseFactory = $optionsResponseFactory;
        return $new;
    }

    public function withNotAllowedResponseFactory(MethodsResponseFactoryInterface $notAllowedResponseFactory): self
    {
        $new = clone $this;
        $new->notAllowedResponseFactory = $notAllowedResponseFactory;
        return $new;
    }

    /**
     * @param string[] $methods
     */
    private function getOptionsResponse(ServerRequestInterface $request, array $methods): ResponseInterface
    {
        return $this->optionsResponseFactory !== null
                ? $this->optionsResponseFactory->create($methods, $request)
                : $this->responseFactory
                    ->createResponse(Status::NO_CONTENT)
                    ->withHeader(Header::ALLOW, implode(', ', $methods));
    }

    /**
     * @param string[] $methods
     */
    private function getMethodNotAllowedResponse(ServerRequestInterface $request, array $methods): ResponseInterface
    {
        return $this->notAllowedResponseFactory !== null
                ? $this->notAllowedResponseFactory->create($methods, $request)
                : $this->responseFactory
                    ->createResponse(Status::METHOD_NOT_ALLOWED)
                    ->withHeader(Header::ALLOW, implode(', ', $methods));
    }
}
