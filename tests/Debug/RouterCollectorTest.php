<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Debug;

use PHPUnit\Framework\MockObject\MockObject;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Router\Debug\RouterCollector;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Tests\Collector\AbstractCollectorTestCase;

final class RouterCollectorTest extends AbstractCollectorTestCase
{
    private MockObject|RouteCollectorInterface|null $routeCollector = null;

    private ?Container $container = null;

    /**
     * @param CollectorInterface|RouterCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
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
        $routeCollector->addGroup(Group::create()->routes(...$this->createRoutes()));

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
            Route::get('/'),
            Group::create('/api')->routes(Route::get('/v1')),
        ];
    }
}
