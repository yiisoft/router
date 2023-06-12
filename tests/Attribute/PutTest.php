<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Put;

class PutTest extends TestCase
{
    public function testRoute(): void
    {
        $route = new Put('/post');

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertEquals([Method::PUT], $route->getData('methods'));
    }
}
