<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;
use Yiisoft\Router\Tests\Support\AssertTrait;
use Yiisoft\Router\Tests\Support\TestController;
use Yiisoft\Router\Tests\Support\TestMiddleware1;
use Yiisoft\Router\Tests\Support\TestMiddleware2;
use Yiisoft\Router\Tests\Support\TestMiddleware3;

final class RouteTest extends TestCase
{
    use AssertTrait;

    public function testSimpleInstance(): void
    {
        $route = new Route(
            methods: [Method::GET],
            pattern: '/',
            action: [TestController::class, 'index'],
            middlewares: [TestMiddleware1::class],
            override: true,
        );

        $this->assertInstanceOf(Route::class, $route);
        $this->assertCount(2, $route->getEnabledMiddlewares());
        $this->assertTrue($route->isOverride());
    }

    public function testDisabledMiddlewares(): void
    {
        $route = new Route(
            methods: [Method::GET],
            pattern: '/',
            action: [TestController::class, 'index'],
            middlewares: [TestMiddleware1::class],
            override: true,
        );
        $route->setDisabledMiddlewares([TestMiddleware2::class]);

        $this->assertCount(1, $route->getDisabledMiddlewares());
        $this->assertSame(TestMiddleware2::class, $route->getDisabledMiddlewares()[0]);
    }

    public function testEnabledMiddlewares(): void
    {
        $route = new Route(
            methods: [Method::GET],
            pattern: '/',
            middlewares: [TestMiddleware1::class, TestMiddleware2::class],
            override: true,
        );
        $route->setDisabledMiddlewares([TestMiddleware2::class]);

        $this->assertCount(1, $route->getEnabledMiddlewares());
        $this->assertSame(TestMiddleware1::class, $route->getEnabledMiddlewares()[0]);
    }

    public function testEmptyMethods(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$methods cannot be empty.');

        new Route([], '');
    }

    public function testName(): void
    {
        $route = (new Route([Method::GET], '/'))->setName('test.route');

        $this->assertSame('test.route', $route->getName());
    }

    public function testNameDefault(): void
    {
        $route = new Route([Method::GET], '/');

        $this->assertSame('GET /', $route->getName());
    }

    public function testNameDefaultWithHosts(): void
    {
        $route = (new Route([Method::GET], '/'))->setHosts(['a.com', 'b.com']);

        $this->assertSame('GET a.com|b.com/', $route->getName());
    }

    public function testMethods(): void
    {
        $route = new Route([Method::POST, Method::HEAD], '/');

        $this->assertSame([Method::POST, Method::HEAD], $route->getMethods());
    }

    public function testPattern(): void
    {
        $route = (new Route([Method::GET], '/test'))->setPattern('/test2');

        $this->assertSame('/test2', $route->getPattern());
    }

    public function testHosts(): void
    {
        $route = (new Route([Method::GET], '/'))
            ->setHosts([
                'https://yiiframework.com/',
                'yf.com',
                'yii.com',
                'yf.ru',
            ]);

        $this->assertSame(
            [
                'https://yiiframework.com',
                'yf.com',
                'yii.com',
                'yf.ru',
            ],
            $route->getHosts()
        );
    }

    public function testDefaults(): void
    {
        $route = (new Route([Method::GET], '/{language}'))->setDefaults([
            'language' => 'en',
            'age' => 42,
        ]);

        $this->assertSame([
            'language' => 'en',
            'age' => '42',
        ], $route->getDefaults());
    }

    public function testOverride(): void
    {
        $route = (new Route([Method::GET], '/'))->setOverride(true);

        $this->assertTrue($route->isOverride());
    }

    public function dataToString(): array
    {
        return [
            ['yiiframework.com/', '/'],
            ['yiiframework.com/yiiframeworkXcom', '/yiiframeworkXcom'],
        ];
    }

    /**
     * @dataProvider dataToString
     */
    public function testToString(string $expected, string $pattern): void
    {
        $route = (new Route([Method::GET, Method::POST], $pattern))
            ->setName('test.route')
            ->setHosts(['yiiframework.com']);

        $this->assertSame('[test.route] GET,POST ' . $expected, (string) $route);
    }

    public function testToStringSimple(): void
    {
        $route = new Route([Method::GET], '/');

        $this->assertSame('GET /', (string) $route);
    }

    public function testInvalidMiddlewares(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $middlewares provided, list of string or array or callable expected.');

        $route = new Route([Method::GET], '/', middlewares: [static fn () => new Response(), (object) ['test' => 1]]);
    }

    public function testInvalidDefaults(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $defaults provided, indexed array of scalar or `Stringable` or null expected.');

        $route = new Route([Method::GET], '/', defaults: ['test' => 1, 'foo' => ['bar']]);
    }

    public function testDebugInfo(): void
    {
        $route = new Route(
            methods: [Method::GET],
            pattern: '/',
            name: 'test',
            action: 'go',
            middlewares: [TestMiddleware3::class, TestMiddleware1::class, TestMiddleware2::class],
            defaults: ['age' => 42],
            hosts: ['example.com'],
            override: true,
            disabledMiddlewares: [TestMiddleware2::class]
        );

        $expected = <<<EOL
Yiisoft\Router\Route Object
(
    [name] => test
    [methods] => Array
        (
            [0] => GET
        )

    [pattern] => /
    [action] => go
    [hosts] => Array
        (
            [0] => example.com
        )

    [defaults] => Array
        (
            [age] => 42
        )

    [override] => 1
    [middlewares] => Array
        (
            [0] => Yiisoft\Router\Tests\Support\TestMiddleware3
            [1] => Yiisoft\Router\Tests\Support\TestMiddleware1
            [2] => Yiisoft\Router\Tests\Support\TestMiddleware2
        )

    [disabledMiddlewares] => Array
        (
            [0] => Yiisoft\Router\Tests\Support\TestMiddleware2
        )

    [enabledMiddlewares] => Array
        (
            [0] => Yiisoft\Router\Tests\Support\TestMiddleware3
            [1] => Yiisoft\Router\Tests\Support\TestMiddleware1
            [2] => go
        )
)

EOL;

        $this->assertSameStringsIgnoringLineEndingsAndSpaces($expected, print_r($route, true));
    }

    public function testDuplicateHosts(): void
    {
        $route = (new Route([Method::GET], '/'))->setHosts(['a.com', 'b.com', 'a.com']);

        $this->assertSame(['a.com', 'b.com'], $route->getHosts());
    }

    public function testInvalidHosts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $hosts provided, list of string expected.');

        $route = new Route([Method::GET], '/', hosts: ['b.com', 123]);
    }

    public function testInvalidMethods(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid $methods provided, list of string expected.');

        $route = new Route([1], '/');
    }
}
