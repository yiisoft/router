<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Dispatcher takes care of executing middleware stack given request object.
 * Default implementation is {@see Dispatcher}. You may want to make your own
 * implementation to support extra functionality in route handlers.
 */
interface DispatcherInterface
{
    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;

    public function withMiddlewares(array $middlewares): DispatcherInterface;
}
