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
        $attribute = new Options('/post');

        $route = $attribute->getRoute();

        $this->assertSame('/post', $route->getData('pattern'));
        $this->assertSame([Method::OPTIONS], $route->getData('methods'));
    }

    public function testOverrideDefaultIsFalse(): void
    {
        $attribute = new Options('/');

        $route = $attribute->getRoute();

        $this->assertFalse($route->getData('override'));
    }

    public function testOverride(): void
    {
        $attribute = new Options('/', override: true);

        $route = $attribute->getRoute();

        $this->assertTrue($route->getData('override'));
    }
}
