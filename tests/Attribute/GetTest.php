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
        $route = new Get('/post');

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertEquals([Method::GET], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $route = new Get('/', override: true);

        $this->assertTrue($route->getData('override'));
    }
}
