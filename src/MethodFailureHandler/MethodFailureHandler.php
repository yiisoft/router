<?php

declare(strict_types=1);

namespace Yiisoft\Router\MethodFailureHandler;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;

final class MethodFailureHandler
{
    private readonly MethodFailureHandlerInterface $allowedMethodsHandler;
    private readonly MethodFailureHandlerInterface $methodNotAllowedHandler;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ?MethodFailureHandlerInterface $allowedMethodsHandler = null,
        ?MethodFailureHandlerInterface $methodNotAllowedHandler = null
    ) {
        $this->allowedMethodsHandler = $allowedMethodsHandler ?? new AllowedMethodsHandler($responseFactory);
        $this->methodNotAllowedHandler = $methodNotAllowedHandler ?? new MethodNotAllowedHandler($responseFactory);
    }

    /**
     * @param string[] $allowedMethods
     */
    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface
    {
        if (empty($allowedMethods)) {
            throw new InvalidArgumentException("Allowed methods can't be empty array.");
        }

        return $request->getMethod() === Method::OPTIONS
            ? $this->allowedMethodsHandler->handle($request, $allowedMethods)
            : $this->methodNotAllowedHandler->handle($request, $allowedMethods);
    }
}
