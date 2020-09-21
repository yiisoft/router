<?php

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestController;
use Yiisoft\Router\MiddlewareFactory;
use Yiisoft\Router\MiddlewareFactoryInterface;
use Yiisoft\Router\Tests\Support\TestMiddleware;

class MiddlewareFactoryTest extends TestCase
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
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(new \stdClass());
    }

    public function testInvalidMiddlewareAddWrongString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create('test');
    }

    public function testInvalidMiddlewareAddWrongStringClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter should be either PSR middleware class name or a callable.');
        $this->getMiddlewareFactory()->create(TestController::class);
    }

    public function testInvalidMiddlewareAddWrongArraySize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['test']);
    }

    public function testInvalidMiddlewareAddWrongArrayClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['class', 'test']);
    }

    public function testInvalidMiddlewareAddWrongArrayType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getMiddlewareFactory()->create(['class' => \Yiisoft\Router\Tests\Support\TestController::class, 'index']);
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
