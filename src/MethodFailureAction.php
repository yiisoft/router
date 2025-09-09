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

/**
 * Default handler that is produces a response with a list of the target resource's supported methods.
 */
final class MethodFailureAction implements MethodFailureActionInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function handle(ServerRequestInterface $request, array $allowedMethods): ResponseInterface
    {
        if (empty($allowedMethods)) {
            throw new InvalidArgumentException("Allowed methods can't be empty array.");
        }

        $status = $request->getMethod() === Method::OPTIONS ? Status::NO_CONTENT : Status::METHOD_NOT_ALLOWED;

        return $this->responseFactory
                ->createResponse($status)
                ->withHeader(Header::ALLOW, implode(', ', $allowedMethods));
    }
}
