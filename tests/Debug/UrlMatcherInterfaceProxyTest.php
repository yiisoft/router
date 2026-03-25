<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Debug;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;
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
    /**
     * @throws ReflectionException
     */
    public function testConstructor(): void
    {
        $urlMatcher = new UrlMatcherStub(MatchingResult::fromFailure([]));
        $collector = new RouterCollector(
            new SimpleContainer([
                CurrentRoute::class => new CurrentRoute(),
            ]),
        );

        $proxy = new UrlMatcherInterfaceProxy($urlMatcher, $collector);

        $this->assertSame($urlMatcher, (new ReflectionProperty($proxy, 'urlMatcher'))->getValue($proxy));
        $this->assertSame($collector, (new ReflectionProperty($proxy, 'routerCollector'))->getValue($proxy));
    }

    public function testBase(): void
    {
        $request = new ServerRequest('GET', '/');
        $route = Route::get('/');
        $arguments = ['a' => '19'];
        $result = MatchingResult::fromSuccess($route, $arguments);

        $currentRoute = new CurrentRoute();
        $currentRoute->setRouteWithArguments($result->route(), $result->arguments());

        $collector = new RouterCollector(
            new SimpleContainer([
                CurrentRoute::class => $currentRoute,
            ]),
        );
        $collector->startup();

        $proxy = new UrlMatcherInterfaceProxy(new UrlMatcherStub($result), $collector);

        $proxyResult = $proxy->match($request);
        $summary = $collector->getSummary();

        $this->assertSame($result, $proxyResult);
        $this->assertArrayHasKey('matchTime', $summary);
        $this->assertGreaterThanOrEqual(0, $summary['matchTime']);
    }
}
