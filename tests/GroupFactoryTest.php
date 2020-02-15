<?php


namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Router\Group;
use Yiisoft\Router\GroupFactory;
use Yiisoft\Router\Route;
use Yiisoft\Router\Tests\Support\Container;

final class GroupFactoryTest extends TestCase
{
    public function testContainerInjected(): void
    {
        $container = $this->getContainer();

        $apiGroup = Group::create(
            '/api',
            [
                Route::get('/info')->name('api-info'),
                Group::create(
                    '/v1',
                    [
                        Route::get('/user')->name('api-v1-user/index'),
                        Route::get('/user/{id}')->name('api-v1-user/view'),
                        Group::create(
                            '/news',
                            [
                                Route::get('/post')->name('api-v1-news-post/index'),
                                Route::get('/post/{id}')->name('api-v1-news-post/view'),
                            ]
                        ),
                        Group::create(
                            '/blog',
                            [
                                Route::get('/post')->name('api-v1-blog-post/index'),
                                Route::get('/post/{id}')->name('api-v1-blog-post/view'),
                            ]
                        ),
                        Route::get('/note')->name('api-v1-note/index'),
                        Route::get('/note/{id}')->name('api-v1-note/view'),
                    ]
                ),
                Group::create(
                    '/v2',
                    [
                        Route::get('/user')->name('api-v2-user/index'),
                        Route::get('/user/{id}')->name('api-v2-user/view'),
                        Group::create(
                            '/news',
                            [
                                Route::get('/post')->name('api-v2-news-post/index'),
                                Route::get('/post/{id}')->name('api-v2-news-post/view'),
                            ]
                        ),
                        Group::create(
                            '/blog',
                            [
                                Route::get('/post')->name('api-v2-blog-post/index'),
                                Route::get('/post/{id}')->name('api-v2-blog-post/view'),
                            ]
                        ),
                        Route::get('/note')->name('api-v2-note/index'),
                        Route::get('/note/{id}')->name('api-v2-note/view'),
                    ]
                )
            ], $container);

        $items = $apiGroup->getItems();

        $this->assertAllRoutesAndGroupsHaveContainer($items);
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }

    private function assertAllRoutesAndGroupsHaveContainer(array $items): void
    {
        $func = function ($item) use (&$func) {
            $this->assertTrue($item->hasContainer());
            if ($item instanceof Group) {
                $items = $item->getItems();
                array_walk($items, $func);
            }
        };
        array_walk($items, $func);
    }
}
