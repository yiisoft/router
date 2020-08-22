<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface DispatcherInterface
{
    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;

    public function withMiddlewares(array $middlewares): DispatcherInterface;
}
