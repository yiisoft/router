<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;
use Yiisoft\Router\Tests\Support\TestController;
use Yiisoft\Router\Tests\Support\TestMiddleware1;
use Yiisoft\Router\Tests\Support\TestMiddleware2;

final class ArrayLikeRouteTest extends TestCase
{
    /**
     * @dataProvider dataArrayDefinition
     */
    public function testName(array $definition, Route $route): void
    {
        $collection = $this->fromArray($definition);

        $keys = [
            'name',
            'pattern',
            'host',
            'hosts',
            'methods',
            'defaults',
            'override',
            'hasMiddlewares',
            'enabledMiddlewares',
        ];
        foreach ($keys as $key) {
            $this->assertSame($collection[0]->getData($key), $route->getData($key));
        }
    }

    public static function dataArrayDefinition(): iterable
    {
        yield 'GET' => [
            [
                '/' => [],
            ],
            Route::get('/'),
        ];
        yield 'POST' => [
            [
                '/' => [
                    'method' => Method::POST,
                ],
            ],
            Route::post('/'),
        ];
        yield 'GET, POST, DELETE' => [
            [
                '/' => [
                    'methods' => [Method::GET, Method::POST, Method::DELETE],
                ],
            ],
            Route::methods([Method::GET, Method::POST, Method::DELETE], '/'),
        ];

        yield 'name' => [
            [
                '/' => [
                    'name' => 'test.route',
                ],
            ],
            Route::get('/')->name('test.route'),
        ];
        yield 'action' => [
            [
                '/' => [
                    'action' => [TestController::class, 'action'],
                ],
            ],
            Route::get('/')
                ->action([TestController::class, 'action']),
        ];
        yield 'middlewares' => [
            [
                '/' => [
                    'action' => [TestController::class, 'action'],
                    'middlewares' => [TestMiddleware1::class, TestMiddleware2::class],
                ],
            ],
            Route::get('/')
                ->middleware(TestMiddleware1::class, TestMiddleware2::class)
                ->action([TestController::class, 'action']),
        ];
    }

    /**
     * @return Route[]
     */
    private function fromArray(array $routes): array
    {
        $collection = [];
        foreach ($routes as $pattern => $data) {
            if (isset($data['method'])) {
                $route = Route::methods([$data['method']], $pattern);
            } elseif (isset($data['methods'])) {
                $route = Route::methods($data['methods'], $pattern);
            } else {
                $route = Route::get($pattern);
            }
            if (isset($data['name'])) {
                $route = $route->name($data['name']);
            }
            if (isset($data['host'])) {
                $route = $route->host($data['host']);
            }
            if (isset($data['defaults'])) {
                $route = $route->defaults($data['defaults']);
            }
            if (isset($data['middlewares'])) {
                $route = $route->middleware(...$data['middlewares']);
            }
            if (isset($data['action'])) {
                $route = $route->action($data['action']);
            }

            $collection[] = $route;
        }

        return $collection;
    }
}
