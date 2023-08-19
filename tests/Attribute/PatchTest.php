<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Patch;

class PatchTest extends TestCase
{
    public function testRoute(): void
    {
        $route = new Patch('/post');

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertEquals([Method::PATCH], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $route = new Patch('/', override: true);

        $this->assertTrue($route->getData('override'));
    }
}
