<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `MethodFailureHandlerInterface` produces a response with a list of the target resource's supported methods.
 */
interface MethodFailureHandlerInterface
{
    /**
     * Produces a response listing resource's allowed methods.
     *
     * @param string[] $allowedMethods a list of the HTTP methods supported by the request's resource
     */
    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface;
}
