<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Post;

class PostTest extends TestCase
{
    public function testRoute(): void
    {
        $route = new Post('/post');

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertEquals([Method::POST], $route->getData('methods'));
    }
}
