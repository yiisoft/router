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
        $attribute = new Patch('/post');

        $route = $attribute->getRoute();

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertSame([Method::PATCH], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $attribute = new Patch('/', override: true);

        $route = $attribute->getRoute();

        $this->assertTrue($route->getData('override'));
    }
}
