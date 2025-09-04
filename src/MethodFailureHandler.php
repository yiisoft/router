<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

class MethodFailureHandler implements MethodFailureHandlerInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory) {
    }

    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface
    {
        if (empty($allowedMethods)) {
            throw new InvalidArgumentException("Allowed methods can't be empty array.");
        }

        return $request->getMethod() === Method::OPTIONS
            ? $this->responseFactory
                ->createResponse(Status::NO_CONTENT)
                ->withHeader(Header::ALLOW, implode(', ', $allowedMethods))
            : $this->responseFactory
                ->createResponse(Status::METHOD_NOT_ALLOWED)
                ->withHeader(Header::ALLOW, implode(', ', $allowedMethods));
    }
}
