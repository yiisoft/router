<?php

declare(strict_types=1);

namespace Yiisoft\Router\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\CurrentRouteInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class Router implements MiddlewareInterface
{
    private UrlMatcherInterface $matcher;
    private ResponseFactoryInterface $responseFactory;
    private MiddlewareDispatcher $dispatcher;
    private CurrentRoute $currentRoute;
    private ?bool $autoResponseOptions = true;

    public function __construct(
        UrlMatcherInterface $matcher,
        ResponseFactoryInterface $responseFactory,
        MiddlewareDispatcher $dispatcher,
        CurrentRouteInterface $currentRoute
    ) {
        $this->matcher = $matcher;
        $this->responseFactory = $responseFactory;
        $this->dispatcher = $dispatcher;
        $this->currentRoute = $currentRoute;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        $this->currentRoute->setUri($request->getUri());

        if ($result->isMethodFailure()) {
            if (
                $this->autoResponseOptions
                && $this->isSameOrigin($request)
                && $request->getMethod() === Method::OPTIONS
            ) {
                return $this->responseFactory->createResponse(Status::NO_CONTENT)
                    ->withHeader('Allow', implode(', ', $result->methods()));
            }
            return $this->responseFactory->createResponse(Status::METHOD_NOT_ALLOWED)
                ->withHeader('Allow', implode(', ', $result->methods()));
        }

        if (!$result->isSuccess()) {
            return $handler->handle($request);
        }

        $this->currentRoute->setRoute($result->route());
        $this->currentRoute->setArguments($result->arguments());

        return $result->withDispatcher($this->dispatcher)->process($request, $handler);
    }

    public function withAutoResponseOptions(): self
    {
        $new = clone $this;
        $new->autoResponseOptions = true;
        return $new;
    }

    public function withoutAutoResponseOptions(): self
    {
        $new = clone $this;
        $new->autoResponseOptions = false;
        return $new;
    }

    private function isSameOrigin(ServerRequestInterface $request): bool
    {
        $origin = $request->getHeaderLine(Header::ORIGIN);
        if ($origin === '') {
            return true;
        }

        $host = $request->getUri()->getHost();
        $port = $request->getUri()->getPort();
        if ($port === null) {
            $port = $request->getUri()->getScheme() === 'https' ? 443 : 80;
        }

        return $origin === "{$request->getUri()->getScheme()}://$host:$port";
    }
}
