<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Debug;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Debug\RouterCollector;
use Yiisoft\Router\Debug\UrlMatcherInterfaceProxy;
use Yiisoft\Router\FastRoute\UrlMatcher;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;

class UrlMatcherInterfaceProxyTest extends TestCase
{
    public function testMatch(): void
    {
        $routeCollector = new RouteCollector();
        $routeCollector->addGroup(Group::create()->routes(Route::get('/')));
        $matcher = new UrlMatcher(new RouteCollection($routeCollector));
        $collector = $this->createMock(RouterCollector::class);
        $time = microtime(true);

        $proxy = new UrlMatcherInterfaceProxy($matcher, $collector);
        $collector->expects($this->once())->method('collect')->with($this->equalToWithDelta(microtime(true) - $time, 0.1));

        $proxy->match(new ServerRequest('GET', '/'));
    }
}
