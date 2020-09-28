<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestController;
use Yiisoft\Router\MiddlewareFactory;
use Yiisoft\Router\MiddlewareFactoryInterface;
use Yiisoft\Router\Tests\Support\TestMiddleware;

final class MiddlewareFactoryTest extends TestCase
{
    public function testCreateFromString(): void
    {
        $container = $this->getContainer([TestMiddleware::class => new TestMiddleware()]);
        $middleware = $this->getMiddlewareFactory($container)->create(TestMiddleware::class);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testCreateFromArray(): void
    {
        $container = $this->getContainer([TestController::class => new TestController()]);
        $middleware = $this->getMiddlewareFactory($container)->create([TestController::class, 'index']);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testInvalidMiddleware(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(new \stdClass());
    }

    public function testValidMiddlewareWithInvoke(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getMiddlewareFactory()->create(
            new class() implements MiddlewareInterface {
                public function __invoke(){
                }
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    return $handler->handle($request);
                }
            }
        );
    }

    public function testCreateFromObjectWithInvoke()
    {
        $middleware = $this->getMiddlewareFactory()->create(
            new class() {
                public function __invoke()
                {
                }
            }
        );

        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testInvalidMiddlewareAddWrongString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create('test');
    }

    public function testInvalidMiddlewareAddWrongStringClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter should be either PSR middleware class name or a callable.');
        $this->getMiddlewareFactory()->create(TestController::class);
    }

    public function testInvalidMiddlewareAddWrongArraySize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['test']);
    }

    public function testInvalidMiddlewareAddWrongArrayClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['class', 'test']);
    }

    public function testInvalidMiddlewareAddWrongArrayType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(
            ['class' => TestController::class, 'index']
        );
    }

    private function getMiddlewareFactory(ContainerInterface $container = null): MiddlewareFactoryInterface
    {
        if ($container !== null) {
            return new MiddlewareFactory($container);
        }

        return new MiddlewareFactory($this->getContainer());
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }
}
