<?php

namespace Yiisoft\Router\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private UrlMatcherInterface $matcher;
    private ResponseFactoryInterface $responseFactory;
    private ContainerInterface $container;

    public function __construct(UrlMatcherInterface $matcher, ResponseFactoryInterface $responseFactory, ContainerInterface $container)
    {
        $this->matcher = $matcher;
        $this->responseFactory = $responseFactory;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        if ($result->isMethodFailure()) {
            return $this->responseFactory->createResponse(405)
                ->withHeader('Allow', implode(', ', $result->methods()));
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        foreach ($result->parameters() as $parameter => $value) {
            $request = $request->withAttribute($parameter, $value);
        }
        $result->setContainer($this->container);

        return $result->process($request, $handler);
    }
}
