<?php

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Uri;
use Yiisoft\Router\Route;
use Yiisoft\Router\CurrentRoute;
use PHPUnit\Framework\TestCase;

class CurrentRouteTest extends TestCase
{
    public function testGetCurrentRoute()
    {
        $route = Route::get('')->name('test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setRoute($route);

        $this->assertSame($route, $currentRoute->getRoute());
    }

    public function testGetCurrentUri()
    {
        $uri = new Uri('/test');
        $currentRoute = new CurrentRoute();
        $currentRoute->setUri($uri);

        $this->assertSame($uri, $currentRoute->getUri());
    }
}
