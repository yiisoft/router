<?php


namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\Group;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouterFactory;
use Yiisoft\Router\RouterInterface;
use Yiisoft\Router\Tests\Support\Container;

final class RouterFactoryTest extends TestCase
{
    public function testContainerInjected(): void
    {
        $container = $this->getContainer();

        $routes = [
            Route::get('/info')->name('api-info'),
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
        ];

        $factory = new RouterFactory($this->getEngineFactory(), $routes);
        $router = $factory($container);
        $items = $router->getItems();

        $this->assertAllRoutesAndGroupsHaveContainer($items);
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

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }

    private function getEngineFactory(): callable
    {
        return new class() {
            public function __invoke(): RouterInterface
            {
                return new class() extends Group implements RouterInterface {
                    public function match(ServerRequestInterface $request): MatchingResult
                    {
                    }

                    public function generate(string $name, array $parameters = []): string
                    {
                    }

                    public function getUriPrefix(): string
                    {
                    }

                    public function setUriPrefix(string $name): void
                    {
                    }
                };
            }
        };
    }
}
