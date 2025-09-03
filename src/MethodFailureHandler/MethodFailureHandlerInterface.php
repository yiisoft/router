<?php

declare(strict_types=1);

namespace Yiisoft\Router\MethodFailureHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MethodFailureHandlerInterface
{
    /**
     * @param string[] $allowedMethods
     *
     * @psalm-param non-empty-array<string> $allowedMethods
     */
    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface;
}
