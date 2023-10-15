<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Get;

class GetTest extends TestCase
{
    public function testRoute(): void
    {
        $attribute = new Get('/post');

        $route = $attribute->getRoute();

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertSame([Method::GET], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $attribute = new Get('/', override: true);

        $route = $attribute->getRoute();

        $this->assertTrue($route->getData('override'));
    }
}
