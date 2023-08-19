<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Head;

class HeadTest extends TestCase
{
    public function testRoute(): void
    {
        $route = new Head('/post');

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertEquals([Method::HEAD], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $route = new Head('/', override: true);

        $this->assertTrue($route->getData('override'));
    }
}
