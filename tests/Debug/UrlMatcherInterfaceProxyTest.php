<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Debug;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Debug\RouterCollector;
use Yiisoft\Router\Debug\UrlMatcherInterfaceProxy;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\Tests\Support\UrlMatcherStub;
use Yiisoft\Test\Support\Container\SimpleContainer;

/**
 * @group yii-debug
 */
final class UrlMatcherInterfaceProxyTest extends TestCase
{
    public function testBase(): void
    {
        $request = new ServerRequest('GET', '/');
        $route = new Route([Method::GET], '/');
        $arguments = ['a' => 19];
        $result = MatchingResult::fromSuccess($route, $arguments);

        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($result->route(), $result->arguments());

        $collector = new RouterCollector(
            new SimpleContainer([
                CurrentRoute::class => $currentRoute,
            ])
        );
        $collector->startup();

        $proxy = new UrlMatcherInterfaceProxy(new UrlMatcherStub($result), $collector);

        $proxyResult = $proxy->match($request);
        $summary = $collector->getSummary();

        $this->assertSame($result, $proxyResult);
        $this->assertGreaterThan(0, $summary['matchTime']);
    }
}
