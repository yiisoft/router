<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\Tests\Support\TestMiddleware1;
use Yiisoft\Router\Tests\Support\TestMiddleware2;
use Yiisoft\Router\Tests\Support\TestMiddleware3;

final class GroupTest extends TestCase
{
    public function testDisabledMiddlewareDefinitions(): void
    {
        $group = (new Group())
            ->setDisabledMiddlewares([TestMiddleware1::class, TestMiddleware3::class]);

        $this->assertCount(2, $group->getDisabledMiddlewares());
    }

    public function testEnabledMiddlewares(): void
    {
        $group = (new Group())
            ->setMiddlewares([TestMiddleware1::class, TestMiddleware2::class, TestMiddleware3::class])
            ->setDisabledMiddlewares([TestMiddleware1::class, TestMiddleware3::class]);

        $this->assertCount(1, $group->getEnabledMiddlewares());
        $this->assertSame(TestMiddleware2::class, $group->getEnabledMiddlewares()[0]);
    }

    public function testSetMiddlewaresAfterGetEnabledMiddlewares(): void
    {
        $group = (new Group())
            ->setMiddlewares([TestMiddleware3::class])
            ->setDisabledMiddlewares([TestMiddleware1::class]);

        $group->getEnabledMiddlewares();

        $group->setMiddlewares([TestMiddleware1::class, TestMiddleware2::class, ...$group->getMiddlewares()]);

        $this->assertSame(
            [TestMiddleware2::class, TestMiddleware3::class],
            $group->getEnabledMiddlewares()
        );
    }

    public function testDisableMiddlewareAfterGetEnabledMiddlewares(): void
    {
        $group = (new Group)
            ->setMiddlewares([TestMiddleware1::class, TestMiddleware2::class, TestMiddleware3::class]);

        $group->getEnabledMiddlewares();

        $group->setDisabledMiddlewares([TestMiddleware1::class, TestMiddleware2::class]);

        $this->assertSame(
            [TestMiddleware3::class],
            $group->getEnabledMiddlewares()
        );
    }

    public function testInvalidMiddlewares(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $middlewares provided, list of string or array or callable expected.');

        $middleware = static fn () => new Response();
        $group = new Group('/api', middlewares: [$middleware, new \stdClass()]);
    }

    public function testHosts(): void
    {
        $group = (new Group())->setHosts(['https://yiiframework.com/']);

        $this->assertSame(['https://yiiframework.com'], $group->getHosts());
    }

    public function testInvalidHosts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $hosts provided, list of string expected.');

        $group = new Group(hosts: ['https://yiiframework.com/', 123]);
    }

    public function testPrefix(): void
    {
        $group = (new Group())->setPrefix('/api');

        $this->assertSame('/api', $group->getPrefix());
    }

    public function testName(): void
    {
        $group = (new Group())->setNamePrefix('api');

        $this->assertSame('api', $group->getNamePrefix());
    }

    public function testCors(): void
    {
        $group = (new Group())->setCorsMiddleware($cors = static fn () => new Response());

        $this->assertSame($cors, $group->getCorsMiddleware());
    }

    public function testRoutes(): void
    {
        $group = (new Group())->setRoutes($routes = [new Route([Method::GET], '')]);

        $this->assertSame($routes, $group->getRoutes());
    }

    public function testInvalidRoutes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $routes provided, array of `Route` or `Group` or `RoutableInterface` instance expected.');

        $group = (new Group())->setRoutes([new Route([Method::GET], ''), new \stdClass()]);
    }
}
