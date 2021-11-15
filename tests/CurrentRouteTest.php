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

        $this->assertSame($route->getName(), $currentRoute->getName());
    }

    public function testGetCurrentUri(): void
    {
        $uri = new Uri('/test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setUri($uri);

        $this->assertSame($uri, $currentRoute->getUri());
    }

    public function testGetParameters(): void
    {
        $parameters = [
            'test' => 'test',
            'foo' => 'bar',
        ];
        $currentRoute = new CurrentRoute();
        $currentRoute->setParameters($parameters);

        $this->assertSame($parameters, $currentRoute->getParameters());
    }

    public function testGetParameter(): void
    {
        $parameters = [
            'test' => 'test',
            'foo' => 'bar',
        ];
        $currentRoute = new CurrentRoute();
        $currentRoute->setParameters($parameters);

        $this->assertSame('bar', $currentRoute->getParameter('foo'));
    }

    public function testGetParameterWithDefault()
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setParameters(['test' => 1]);

        $this->assertSame('bar', $currentRoute->getParameter('foo', 'bar'));
    }

    public function testGetParameterWithNonExist()
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setParameters(['test' => 1]);

        $this->assertNull($currentRoute->getParameter('foo'));
    }

    public function testSetRouteTwice()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set route since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setRoute(Route::get('')->name('test'));
        $currentRoute->setRoute(Route::get('/home')->name('home'));
    }

    public function testSetUriTwice()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set URI since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setUri(new Uri(''));
        $currentRoute->setUri(new Uri('home'));
    }

    public function testSetParametersTwice()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set parameters since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setParameters(['foo' => 'bar']);
        $currentRoute->setParameters(['id' => 1]);
    }
}
