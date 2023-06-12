<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Attribute\Options;

class OptionsTest extends TestCase
{
    public function testRoute(): void
    {
        $route = new Options('/post');

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertEquals([Method::OPTIONS], $route->getData('methods'));
    }
}
