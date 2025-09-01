<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;

class MethodNotAllowedHandler implements MethodFailureHandlerInterface
{
    private array $methods;

    public function __construct(private readonly ResponseFactoryInterface $responseFactory) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory
                ->createResponse(Status::METHOD_NOT_ALLOWED)
                ->withHeader(Header::ALLOW, implode(', ', $this->methods));
    }

    public function withAllowedMethods(array $methods): self
    {
        $new = clone $this;
        $new->methods = $methods;
        return $new;
    }
}
