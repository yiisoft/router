<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `MethodsResponseFactoryInterface` produces a response with a list of the target resource's supported methods.
 */
interface MethodsResponseFactoryInterface
{
    /**
     * Handles allowed methods and produces a response.
     *
     * @param array $methods a list of the HTTP methods supported by the request's resource
     * 
     */
    public function create(array $methods, ServerRequestInterface $request): ResponseInterface;
}
