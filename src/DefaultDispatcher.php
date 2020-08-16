<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Interfaces\DispatcherInterface;

use function array_shift;

class DefaultDispatcher implements DispatcherInterface
{
    use MiddlewareAwareTrait;

    private ?ContainerInterface $container;

    public function __construct(?ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // TODO: call it only once
        $this->prepareMiddlewares();

        /** @var MiddlewareInterface|null $middleware */
        $middleware = array_shift($this->middlewares);
        if ($middleware === null) {
            throw new \Exception('There must be at least one middleware.');
        }

        return $middleware->process($request, $this);
    }

    private function prepareMiddlewares(): void
    {
        // TODO: converts all middlewares to MiddlewareInterface, here goes all all wrapCallable code
        // TODO: also before preparing of each middleware validation is done
    }
}
