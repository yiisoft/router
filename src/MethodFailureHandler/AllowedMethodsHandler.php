<?php

declare(strict_types=1);

namespace Yiisoft\Router\MethodFailureHandler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;

final class AllowedMethodsHandler implements MethodFailureHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::NO_CONTENT)
            ->withHeader(Header::ALLOW, implode(', ', $allowedMethods));
    }
}
