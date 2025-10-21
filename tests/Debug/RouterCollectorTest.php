<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Debug;

use PHPUnit\Framework\MockObject\MockObject;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Http\Method;
use Yiisoft\Router\Builder\GroupBuilder;
use Yiisoft\Router\Builder\RouteBuilder;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Debug\RouterCollector;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Tests\Shared\AbstractCollectorTestCase;

/**
 * @group yii-debug
 */
final class RouterCollectorTest extends AbstractCollectorTestCase
{
    private MockObject|RouteCollectorInterface|null $routeCollector = null;

    private ?Container $container = null;

    public function testWithoutCurrentRoute(): void
    {
        $collector = new RouterCollector(
            new SimpleContainer()
        );
        $collector->startup();

        $summary = $collector->getSummary();

        $this->assertEmpty($summary);
    }

    public function testWithoutRouteCollection(): void
    {
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

        $collected = $collector->getCollected();

        $this->assertSame(['currentRoute'], array_keys($collected));
        $this->assertSame(
            ['matchTime', 'name', 'pattern', 'arguments', 'hosts', 'uri', 'action', 'middlewares'],
            array_keys($collected['currentRoute'])
        );
    }

    /**
     * @param CollectorInterface|RouterCollector $collector
     */
    protected function collectTestData(CollectorInterface|RouterCollector $collector): void
    {
        $routes = $this->createRoutes();
        $this->routeCollector
            ->method('getItems')
            ->willReturn($routes);
        $collector->collect(0.001);
    }

    protected function getCollector(): CollectorInterface
    {
        $this->routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector = new RouteCollector();
        $routeCollector->addRoute(...$this->createRoutes());

        $config = ContainerConfig::create()
            ->withDefinitions([
                UrlMatcherInterface::class => $this->routeCollector,
                RouteCollectionInterface::class => RouteCollection::class,
                RouteCollectorInterface::class => $routeCollector,
            ]);
        $this->container = new Container($config);

        return new RouterCollector($this->container);
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);
        $this->assertArrayHasKey('routes', $data);
        $this->assertArrayHasKey('routesTree', $data);
        $this->assertArrayHasKey('routeTime', $data);
        $this->assertEquals(
            $this->container->get(RouteCollectionInterface::class)->getRoutes(),
            $data['routes']
        );
        $this->assertEquals(
            $this->container->get(RouteCollectionInterface::class)->getRouteTree(),
            $data['routesTree']
        );
        $this->assertEquals(
            0.001,
            $data['routeTime']
        );
    }

    private function createRoutes(): array
    {
        return [
            new Route([Method::GET], '/'),
            GroupBuilder::create('/api')->routes(RouteBuilder::get('/v1')),
        ];
    }
}
