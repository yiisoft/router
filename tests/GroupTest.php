<?php


namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class GroupTest extends TestCase
{
    public function testAddRoute(): void
    {
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $group = new Group();
        $group->addRoute($listRoute);
        $group->addRoute($viewRoute);

        $this->assertCount(2, $group->getItems());
        $this->assertSame($listRoute, $group->getItems()[0]);
        $this->assertSame($viewRoute, $group->getItems()[1]);
    }

    public function testAddMiddleware(): void
    {
        $group = new Group();

        $middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();

        $group
            ->addMiddleware($middleware1)
            ->addMiddleware($middleware2);

        $this->assertCount(2, $group->getMiddlewares());
        $this->assertSame($middleware1, $group->getMiddlewares()[0]);
        $this->assertSame($middleware2, $group->getMiddlewares()[1]);
    }

    public function testAddGroup(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();

        $root = new Group();
        $root->addGroup('/api', static function (Group $group) use ($logoutRoute, $listRoute, $viewRoute, $middleware1, $middleware2) {
            $group->addRoute($logoutRoute);
            $group->addGroup('/post', static function (Group $group) use ($listRoute, $viewRoute) {
                $group->addRoute($listRoute);
                $group->addRoute($viewRoute);
            });

            $group->addMiddleware($middleware1);
            $group->addMiddleware($middleware2);
        });

        $this->assertCount(1, $root->getItems());
        $api = $root->getItems()[0];

        $this->assertSame('/api', $api->getPrefix());
        $this->assertCount(2, $api->getItems());
        $this->assertSame($logoutRoute, $api->getItems()[0]);

        /** @var Group $postGroup */
        $postGroup = $api->getItems()[1];
        $this->assertInstanceOf(Group::class, $postGroup);
        $this->assertCount(2, $api->getMiddlewares());
        $this->assertSame($middleware1, $api->getMiddlewares()[0]);
        $this->assertSame($middleware2, $api->getMiddlewares()[1]);

        $this->assertSame('/post', $postGroup->getPrefix());
        $this->assertCount(2, $postGroup->getItems());
        $this->assertSame($listRoute, $postGroup->getItems()[0]);
        $this->assertSame($viewRoute, $postGroup->getItems()[1]);
        $this->assertEmpty($postGroup->getMiddlewares());
    }
}
