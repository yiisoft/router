<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use LogicException;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;

class CurrentRouteTest extends TestCase
{
    public function testGetCurrentRoute(): void
    {
        $route = Route::get('')->name('test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRoute($route);

        $this->assertSame($route, $currentRoute->getRoute());
    }

    public function testGetCurrentRouteName(): void
    {
        $route = Route::get('')->name('test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRoute($route);

        $this->assertSame($route->getData(Route::NAME), $currentRoute->getName());
    }

    public function testGetCurrentUri(): void
    {
        $uri = new Uri('/test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setUri($uri);

        $this->assertSame($uri, $currentRoute->getUri());
    }

    public function testGetArguments(): void
    {
        $parameters = [
            'test' => 'test',
            'foo' => 'bar',
        ];
        $currentRoute = new CurrentRoute();
        $currentRoute->setArguments($parameters);

        $this->assertSame($parameters, $currentRoute->getArguments());
    }

    public function testGetArgument(): void
    {
        $parameters = [
            'test' => 'test',
            'foo' => 'bar',
        ];
        $currentRoute = new CurrentRoute();
        $currentRoute->setArguments($parameters);

        $this->assertSame('bar', $currentRoute->getArgument('foo'));
    }

    public function testGetArgumentWithDefault(): void
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setArguments(['test' => 1]);

        $this->assertSame('bar', $currentRoute->getArgument('foo', 'bar'));
    }

    public function testGetArgumentWithNonExist(): void
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setArguments(['test' => 1]);

        $this->assertNull($currentRoute->getArgument('foo'));
    }

    public function testSetRouteTwice(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set route since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setRoute(Route::get('')->name('test'));
        $currentRoute->setRoute(Route::get('/home')->name('home'));
    }

    public function testSetUriTwice(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set URI since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setUri(new Uri(''));
        $currentRoute->setUri(new Uri('home'));
    }

    public function testSetArgumentsTwice(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set arguments since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setArguments(['foo' => 'bar']);
        $currentRoute->setArguments(['id' => 1]);
    }
}
