<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Delete;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    public function testRoute(): void
    {
        $attribute = new Delete('/post');

        $route = $attribute->getRoute();

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertSame([Method::DELETE], $route->getData('methods'));
    }

    public function testOverride(): void
    {
        $attribute = new Delete('/', override: true);

        $route = $attribute->getRoute();

        $this->assertTrue($route->getData('override'));
    }
}
