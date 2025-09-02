<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;

class AllowedMethodsHandler implements MethodFailureHandlerInterface
{
    private array $methods;

    public function __construct(private readonly ResponseFactoryInterface $responseFactory) {
    }

    /**
     * @throws InvalidArgumentException when methods are empty or not set
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->methods)) {
            throw new InvalidArgumentException("Allowed methods can't be empty array.");
        }

        return $this->responseFactory
                ->createResponse(Status::NO_CONTENT)
                ->withHeader(Header::ALLOW, implode(', ', $this->methods));
    }

    public function withAllowedMethods(array $methods): self
    {
        $new = clone $this;
        $new->methods = $methods;
        return $new;
    }
}
