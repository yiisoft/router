<?php

declare(strict_types=1);

namespace Yiisoft\Router\MethodFailureHandler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;

final class MethodNotAllowedHandler implements MethodFailureHandlerInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(Status::METHOD_NOT_ALLOWED)
            ->withHeader(Header::ALLOW, implode(', ', $allowedMethods));
    }
}
