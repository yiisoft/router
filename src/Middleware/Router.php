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
    private bool $handleMethodFailure = true;

    private MiddlewareDispatcher $dispatcher;

    public function __construct(
        private UrlMatcherInterface $matcher,
        private ResponseFactoryInterface $responseFactory,
        MiddlewareFactory $middlewareFactory,
        private CurrentRoute $currentRoute,
        ?EventDispatcherInterface $eventDispatcher = null,
        private ?RequestHandlerInterface $optionsHandler = null,
        private ?RequestHandlerInterface $notAllowedHandler = null
    ) {
        $this->dispatcher = new MiddlewareDispatcher($middlewareFactory, $eventDispatcher);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        $this->currentRoute->setUri($request->getUri());

        if ($this->handleMethodFailure && $result->isMethodFailure()) {
            return $request->getMethod() === Method::OPTIONS
                    ? $this->getOptionsResponse($request, $result)
                    : $this->getMethodNotAllowedResponse($request, $result);
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        $this->currentRoute->setRouteWithArguments($result->route(), $result->arguments());

        return $result
            ->withDispatcher($this->dispatcher)
            ->process($request, $handler);
    }

    public function withHandleMethodFailure(bool $handleMethodFailure): self
    {
        $new = clone $this;
        $new->handleMethodFailure = $handleMethodFailure;
        return $new;
    }

    private function getOptionsResponse(ServerRequestInterface $request, MatchingResult $result): ResponseInterface
    {
        return $this->optionsHandler !== null
                ? $this->optionsHandler->handle($request, $result)
                : $this->responseFactory
                    ->createResponse(Status::NO_CONTENT)
                    ->withHeader(Header::ALLOW, $result->methods());
    }

    private function getMethodNotAllowedResponse(ServerRequestInterface $request, MatchingResult $result): ResponseInterface
    {
        return $this->notAllowedHandler !== null
                ? $this->notAllowedHandler->handle($request, $result)
                : $this->responseFactory
                    ->createResponse(Status::METHOD_NOT_ALLOWED)
                    ->withHeader(Header::ALLOW, implode(', ', $result->methods()));
    }
}
