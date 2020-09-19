<?php

declare(strict_types=1);

namespace Yiisoft\Router\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\DispatcherInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private UrlMatcherInterface $matcher;
    private ResponseFactoryInterface $responseFactory;
    private DispatcherInterface $dispatcher;

    public function __construct(UrlMatcherInterface $matcher, ResponseFactoryInterface $responseFactory, DispatcherInterface $dispatcher)
    {
        $this->matcher = $matcher;
        $this->responseFactory = $responseFactory;
        $this->dispatcher = $dispatcher;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        if ($result->isMethodFailure()) {
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
