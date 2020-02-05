<?php

namespace Yiisoft\Router\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

/**
 * Callback wraps arbitrary PHP callback into object matching {@see MiddlewareInterface}.
 */
final class Callback implements MiddlewareInterface
{
    private $callback;
    private ContainerInterface $container;

    public function __construct(callable $callback, ContainerInterface $container)
    {
        $this->callback = $callback;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return (new Injector($this->container))->invoke($this->callback, [$request, $handler]);
    }
}
