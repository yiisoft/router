<?php
namespace Yiisoft\Router\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Method;
use Yiisoft\Router\UrlMatcherInterface;

class Router implements MiddlewareInterface
{
    private $matcher;
    private $responseFactory;

    public function __construct(UrlMatcherInterface $matcher, ResponseFactoryInterface $responseFactory)
    {
        $this->matcher = $matcher;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->matcher->match($request);

        if ($result->isSuccess()) {
            foreach ($result->parameters() as $parameter => $value) {
                $request = $request->withAttribute($parameter, $value);
            }
        } else {
            // method not allowed
            if ($result->methods() !== Method::ANY) {
                return $this->responseFactory->createResponse(405)
                    ->withHeader('Allow', implode(', ', $result->methods()));
            }

            // not found
            return $handler->handle($request);
        }

        return $result->process($request, $handler);
    }
}
