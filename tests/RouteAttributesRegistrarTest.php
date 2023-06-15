<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Yiisoft\Router\RouteAttributesRegistrar;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\Tests\Support\TestController;

class RouteAttributesRegistrarTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        class_exists(TestController::class);
    }
    public function testRegister(): void
    {
        $routeCollector = new RouteCollector();
        $registrar = new RouteAttributesRegistrar($routeCollector);

        $registrar->register();

        $this->assertCount(1, $items = $routeCollector->getItems());
        $this->assertCount(1, $items[0]->getData('routes'));
        $this->assertCount(1, $items[0]->getData('routes')[0]->getBuiltMiddlewares());
        $this->assertSame([TestController::class, 'attributeAction'], $items[0]->getData('routes')[0]->getBuiltMiddlewares()[0]);
    }
}
