<?php

namespace Yiisoft\Router\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Injector\Injector;

/**
 * ActionCaller maps a route to specified class instance and method.
 *
 * Dependencies are automatically injected into both method
 * and constructor based on types specified.
 */
final class ActionCaller implements MiddlewareInterface
{
    private string $class;
    private string $method;
    private ContainerInterface $container;

    public function __construct(string $class, string $method, ContainerInterface $container)
    {
        $this->class = $class;
        $this->method = $method;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $controller = $this->container->get($this->class);
        $response = (new Injector($this->container))->invoke([$controller, $this->method], [$request, $handler]);
        $handler->handle($request);
        return $response;
    }
}
