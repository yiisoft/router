<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * `MethodFailureHandlerInterface` produces a response with a list of the target resource's supported methods.
 */
interface MethodFailureHandlerInterface extends RequestHandlerInterface
{
    /**
     * Creates new instance of handler with supported methods set.
     *
     * @param string[] $methods a list of the HTTP methods supported by the request's resource
     */
    public function withAllowedMethods(array $methods): self;
}
