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
    public function testGetName(): void
    {
        $route = Route::get('')->name('test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getData('name'), $currentRoute->getName());
    }

    public function testGetHost(): void
    {
        $route = Route::get('')->host('test.com');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getData('host'), $currentRoute->getHost());
    }

    public function testGetPattern(): void
    {
        $route = Route::get('/home');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getData('pattern'), $currentRoute->getPattern());
    }

    public function testGetMethods(): void
    {
        $route = Route::get('');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getData('methods'), $currentRoute->getMethods());
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
        $currentRoute->setRouteWithArguments(Route::get(''), $parameters);

        $this->assertSame($parameters, $currentRoute->getArguments());
    }

    public function testGetArgument(): void
    {
        $parameters = [
            'test' => 'test',
            'foo' => 'bar',
        ];
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(Route::get(''), $parameters);

        $this->assertSame('bar', $currentRoute->getArgument('foo'));
    }

    public function testGetArgumentWithDefault(): void
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(Route::get(''), ['test' => 1]);

        $this->assertSame('bar', $currentRoute->getArgument('foo', 'bar'));
    }

    public function testGetArgumentWithNonExist(): void
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(Route::get(''), ['test' => 1]);

        $this->assertNull($currentRoute->getArgument('foo'));
    }

    public function testSetRouteTwice(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set route/arguments since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(Route::get('')->name('test'), []);
        $currentRoute->setRouteWithArguments(Route::get('/home')->name('home'), []);
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
        $this->expectExceptionMessage('Can not set route/arguments since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(Route::get(''), ['foo' => 'bar']);
        $currentRoute->setRouteWithArguments(Route::get(''), ['id' => 1]);
    }
}
