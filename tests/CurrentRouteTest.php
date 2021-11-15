<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

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
}
