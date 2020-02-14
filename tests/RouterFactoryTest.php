<?php

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Router\RouterFactory;
use Yiisoft\Router\RouterInterface;
use Yiisoft\Router\Tests\Support\Container;

final class RouterFactoryTest extends TestCase
{
    public function testContainerInjected(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('withContainer')
            ->willReturn($this->createMock(RouterInterface::class));
        $router
            ->expects($this->once())
            ->method('hasContainer');

        $factory = new RouterFactory(fn () => $router);
        $newRouter = $factory($this->getContainer());
        $this->assertNotSame($router, $newRouter);
    }

    public function testContainerNotInjected(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('withContainer');
        $router
            ->expects($this->once())
            ->method('hasContainer')
            ->willReturn(true);

        $factory = new RouterFactory(fn () => $router);
        $newRouter = $factory($this->getContainer());
        $this->assertSame($router, $newRouter);
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }
}
