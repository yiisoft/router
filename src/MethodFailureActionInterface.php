<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `MethodFailureActionInterface` produces a response with a list of the target resource's supported methods.
 */
interface MethodFailureActionInterface
{
    /**
     * Produces a response listing resource's allowed methods.
     *
     * @param string[] $allowedMethods a list of the HTTP methods supported by the request's resource
     */
    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface;
}
