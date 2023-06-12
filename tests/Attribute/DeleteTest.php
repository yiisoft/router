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
        $route = new Delete('/post');

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertEquals([Method::DELETE], $route->getData('methods'));
    }
}
