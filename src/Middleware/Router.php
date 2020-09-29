<?php

declare(strict_types=1);

namespace Yiisoft\Router\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\MiddlewareDispatcher;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private UrlMatcherInterface $matcher;
    private ResponseFactoryInterface $responseFactory;
    private MiddlewareDispatcher $dispatcher;
    private bool $optionsAutoReponse = false;

    public function __construct(UrlMatcherInterface $matcher, ResponseFactoryInterface $responseFactory, MiddlewareDispatcher $dispatcher)
    {
        $this->matcher = $matcher;
        $this->responseFactory = $responseFactory;
        $this->dispatcher = $dispatcher;
    }

    public function withOptionsAutoResponse(): self
    {
        $new = clone $this;
        $new->optionsAutoReponse = true;

        return $new;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        if ($result->isMethodFailure()) {
            if ($this->optionsAutoReponse && $request->getMethod() === Method::OPTIONS) {
                return $this->responseFactory->createResponse(Status::NO_CONTENT)
                    ->withHeader('Allow', implode(', ', $result->methods()));
            }

            return $this->responseFactory->createResponse(Status::METHOD_NOT_ALLOWED)
                ->withHeader('Allow', implode(', ', $result->methods()));
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        foreach ($result->parameters() as $parameter => $value) {
            $request = $request->withAttribute($parameter, $value);
        }

        return $result->withDispatcher($this->dispatcher)->process($request, $handler);
    }
}
