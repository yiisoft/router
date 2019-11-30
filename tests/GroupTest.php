<?php


namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Group;
use Yiisoft\Router\GroupMiddlewareInterface;
use Yiisoft\Router\Route;

class GroupTest extends TestCase
{
    public function testGetPrefix(): void
    {
        $group = new Group('/api');
        $this->assertSame('/api', $group->getPrefix());
    }

    public function testGetRoutes(): void
    {
        $routes = [
            Route::get('/posts'),
            Route::get('/comments')
        ];

        $group = new Group('/api');

        foreach ($routes as $route) {
            $group = $group->addRoute($route);
        }

        $this->assertCount(2, $group->getRoutes());
        $this->assertSame($routes, $group->getRoutes());
    }

    public function testGetMiddleware(): void
    {
        $middleware = $this->getMockBuilder(GroupMiddlewareInterface::class)->getMock();

        $group = new Group('/api', $middleware);
        $this->assertSame($middleware, $group->getMiddleware());
    }
}
