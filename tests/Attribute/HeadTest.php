<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Head;

final class HeadTest extends TestCase
{
    public function testRoute(): void
    {
        $attribute = new Head('/post');

        $route = $attribute->getRoute();

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertSame([Method::HEAD], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $attribute = new Head('/', override: true);

        $route = $attribute->getRoute();

        $this->assertTrue($route->getData('override'));
    }
}
