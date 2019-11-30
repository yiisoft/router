<?php


namespace Yiisoft\Router;


use Psr\Http\Server\MiddlewareInterface;

interface GroupMiddlewareInterface extends MiddlewareInterface
{
    public function withRouteMiddleware(MiddlewareInterface $middleware): self;
}
