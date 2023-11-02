<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Route;

class RouteTest extends TestCase
{
    public function testRoute(): void
    {
        $attribute = new Route([Method::GET, Method::HEAD], '/post');

        $route = $attribute->getRoute();

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertSame([Method::GET, Method::HEAD], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $attribute = new Route([Method::GET, Method::HEAD], '/', override: true);

        $route = $attribute->getRoute();

        $this->assertTrue($route->getData('override'));
    }
}
