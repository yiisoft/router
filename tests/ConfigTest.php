<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Di\StateResetter;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteCollectorInterface;

use function dirname;

final class ConfigTest extends TestCase
{
    public function testRouteCollector(): void
    {
        $container = $this->createContainer();

        $routerCollector = $container->get(RouteCollectorInterface::class);
        $this->assertInstanceOf(RouteCollector::class, $routerCollector);
    }

    public function testCurrentRoute(): void
    {
        $container = $this->createContainer();

        $currentRoute = $container->get(CurrentRoute::class);
        $currentRoute->setRouteWithArguments(Route::get('/main'), ['name' => 'hello']);
        $currentRoute->setUri(new Uri('http://example.com/'));

        $container
            ->get(StateResetter::class)
            ->reset();

        $this->assertNull($currentRoute->getName());
        $this->assertNull($currentRoute->getUri());
        $this->assertSame([], $currentRoute->getArguments());
    }

    private function createContainer(): Container
    {
        return new Container(
            ContainerConfig::create()->withDefinitions(
                $this->getContainerDefinitions()
            )
        );
    }

    private function getContainerDefinitions(): array
    {
        $params = require dirname(__DIR__) . '/config/params.php';
        return require dirname(__DIR__) . '/config/di.php';
    }
}
