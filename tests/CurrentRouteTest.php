<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use LogicException;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;

class CurrentRouteTest extends TestCase
{
    public function testGetName(): void
    {
        $route = new Route([Method::GET], '', 'test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getName(), $currentRoute->getName());
    }

    public function testGetHost(): void
    {
        $route = new Route([Method::GET], '', hosts: ['test.com']);
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getHosts(), $currentRoute->getHosts());
    }

    public function testGetPattern(): void
    {
        $route = new Route([Method::GET], '/home');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getPattern(), $currentRoute->getPattern());
    }

    public function testGetMethods(): void
    {
        $route = new Route([Method::GET], '');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($route, []);

        $this->assertSame($route->getMethods(), $currentRoute->getMethods());
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
        $currentRoute->setRouteWithArguments(new Route([Method::GET], ''), $parameters);

        $this->assertSame($parameters, $currentRoute->getArguments());
    }

    public function testGetArgument(): void
    {
        $parameters = [
            'test' => 'test',
            'foo' => 'bar',
        ];
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(new Route([Method::GET], ''), $parameters);

        $this->assertSame('bar', $currentRoute->getArgument('foo'));
    }

    public function testGetArgumentWithDefault(): void
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(new Route([Method::GET], ''), ['test' => 1]);

        $this->assertSame('bar', $currentRoute->getArgument('foo', 'bar'));
    }

    public function testGetArgumentWithNonExist(): void
    {
        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(new Route([Method::GET], ''), ['test' => 1]);

        $this->assertNull($currentRoute->getArgument('foo'));
    }

    public function testSetRouteTwice(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Can not set route/arguments since it was already set.');

        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments(new Route([Method::GET], '', 'test'), []);
        $currentRoute->setRouteWithArguments(new Route([Method::GET], '/home', 'home'), []);
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
        $currentRoute->setRouteWithArguments(new Route([Method::GET], ''), ['foo' => 'bar']);
        $currentRoute->setRouteWithArguments(new Route([Method::GET], ''), ['id' => 1]);
    }
}
