<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\Route;
use Yiisoft\Router\Tests\Support\AssertTrait;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestController;
use Yiisoft\Router\Tests\Support\TestMiddleware1;
use Yiisoft\Router\Tests\Support\TestMiddleware2;
use Yiisoft\Router\Tests\Support\TestMiddleware3;

final class RouteTest extends TestCase
{
    use AssertTrait;

    public function testName(): void
    {
        $route = Route::get('/')->name('test.route');

        $this->assertSame('test.route', $route->getData('name'));
    }

    public function testNameDefault(): void
    {
        $route = Route::get('/');

        $this->assertSame('GET /', $route->getData('name'));
    }

    public function testNameDefaultWithHosts(): void
    {
        $route = Route::get('/')->hosts('a.com', 'b.com');

        $this->assertSame('GET a.com|b.com/', $route->getData('name'));
    }

    public function testMethods(): void
    {
        $route = Route::methods([Method::POST, Method::HEAD], '/');

        $this->assertSame([Method::POST, Method::HEAD], $route->getData('methods'));
    }

    public function testGetDataWithWrongKey(): void
    {
        $route = Route::get('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown data key: wrong');

        $route->getData('wrong');
    }

    public function testGetMethod(): void
    {
        $route = Route::get('/');

        $this->assertSame([Method::GET], $route->getData('methods'));
    }

    public function testPostMethod(): void
    {
        $route = Route::post('/');

        $this->assertSame([Method::POST], $route->getData('methods'));
    }

    public function testPutMethod(): void
    {
        $route = Route::put('/');

        $this->assertSame([Method::PUT], $route->getData('methods'));
    }

    public function testDeleteMethod(): void
    {
        $route = Route::delete('/');

        $this->assertSame([Method::DELETE], $route->getData('methods'));
    }

    public function testPatchMethod(): void
    {
        $route = Route::patch('/');

        $this->assertSame([Method::PATCH], $route->getData('methods'));
    }

    public function testHeadMethod(): void
    {
        $route = Route::head('/');

        $this->assertSame([Method::HEAD], $route->getData('methods'));
    }

    public function testAlias(): void
    {
        $route1 = Route::head('/')->name('home');
        $route2 = Route::head('/main')->alias('new-home')->name('new-home');

        $this->assertSame(null, $route1->getData('alias'));
        $this->assertSame('new-home', $route2->getData('alias'));
    }

    public function testOptionsMethod(): void
    {
        $route = Route::options('/');

        $this->assertSame([Method::OPTIONS], $route->getData('methods'));
    }

    public function testPattern(): void
    {
        $route = Route::get('/test')->pattern('/test2');

        $this->assertSame('/test2', $route->getData('pattern'));
    }

    public function testHost(): void
    {
        $route = Route::get('/')->host('https://yiiframework.com/');

        $this->assertSame('https://yiiframework.com', $route->getData('host'));
    }

    public function testHosts(): void
    {
        $route = Route::get('/')
            ->hosts(
                'https://yiiframework.com/',
                'yf.com',
                'yii.com',
                'yf.ru'
            );

        $this->assertSame(
            [
                'https://yiiframework.com',
                'yf.com',
                'yii.com',
                'yf.ru',
            ],
            $route->getData('hosts')
        );
    }

    public function testMultipleHosts(): void
    {
        $route = Route::get('/')
            ->host('https://yiiframework.com/');
        $multipleRoute = Route::get('/')
            ->hosts(
                'https://yiiframework.com/',
                'https://yiiframework.ru/'
            );

        $this->assertCount(1, $route->getData('hosts'));
        $this->assertCount(2, $multipleRoute->getData('hosts'));
    }

    public function testDefaults(): void
    {
        $route = Route::get('/{language}')->defaults([
            'language' => 'en',
            'age' => 42,
        ]);

        $this->assertSame([
            'language' => 'en',
            'age' => '42',
        ], $route->getData('defaults'));
    }

    public function testOverride(): void
    {
        $route = Route::get('/')->override();

        $this->assertTrue($route->getData('override'));
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
        $route = Route::methods([Method::GET, Method::POST], $pattern)
            ->name('test.route')
            ->host('yiiframework.com');

        $this->assertSame('[test.route] GET,POST ' . $expected, (string)$route);
    }

    public function testToStringSimple(): void
    {
        $route = Route::get('/');

        $this->assertSame('GET /', (string)$route);
    }

    public function testDispatcherInjecting(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer(
            [
                TestController::class => new TestController(),
            ]
        );

        $route = Route::get('/')->action([TestController::class, 'index']);

        $response = $this
            ->getDispatcher($container)
            ->withMiddlewares($route->getData('enabledMiddlewares'))
            ->dispatch($request, $this->getRequestHandler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewareAfterAction(): void
    {
        $route = Route::get('/')->action([TestController::class, 'index']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('middleware() can not be used after action().');
        $route->middleware(static fn () => new Response());
    }

    public function testPrependMiddlewareBeforeAction(): void
    {
        $route = Route::get('/');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('prependMiddleware() can not be used before action().');
        $route->prependMiddleware(static fn () => new Response());
    }

    public function testDisabledMiddlewareDefinitions(): void
    {
        $request = new ServerRequest('GET', '/');

        $route = Route::get('/')
            ->middleware(TestMiddleware1::class, TestMiddleware2::class, TestMiddleware3::class)
            ->action([TestController::class, 'index'])
            ->disableMiddleware(TestMiddleware1::class, TestMiddleware3::class);

        $dispatcher = $this
            ->getDispatcher(
                $this->getContainer([
                    TestMiddleware1::class => new TestMiddleware1(),
                    TestMiddleware2::class => new TestMiddleware2(),
                    TestMiddleware3::class => new TestMiddleware3(),
                    TestController::class => new TestController(),
                ])
            )
            ->withMiddlewares($route->getData('enabledMiddlewares'));

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('2', (string) $response->getBody());
    }

    public function testPrependMiddlewareDefinitions(): void
    {
        $request = new ServerRequest('GET', '/');

        $route = Route::get('/')
            ->middleware(TestMiddleware3::class)
            ->action([TestController::class, 'index'])
            ->prependMiddleware(TestMiddleware1::class, TestMiddleware2::class);

        $response = $this
            ->getDispatcher(
                $this->getContainer([
                    TestMiddleware1::class => new TestMiddleware1(),
                    TestMiddleware2::class => new TestMiddleware2(),
                    TestMiddleware3::class => new TestMiddleware3(),
                    TestController::class => new TestController(),
                ])
            )
            ->withMiddlewares($route->getData('enabledMiddlewares'))
            ->dispatch($request, $this->getRequestHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('123', (string) $response->getBody());
    }

    public function testPrependMiddlewaresAfterGetEnabledMiddlewares(): void
    {
        $route = Route::get('/')
            ->middleware(TestMiddleware3::class)
            ->disableMiddleware(TestMiddleware1::class)
            ->action([TestController::class, 'index']);

        $route->getData('enabledMiddlewares');

        $route = $route->prependMiddleware(TestMiddleware1::class, TestMiddleware2::class);

        $this->assertSame(
            [TestMiddleware2::class, TestMiddleware3::class, [TestController::class, 'index']],
            $route->getData('enabledMiddlewares')
        );
    }

    public function testAddMiddlewareAfterGetEnabledMiddlewares(): void
    {
        $route = Route::get('/')
            ->middleware(TestMiddleware3::class);

        $route->getData('enabledMiddlewares');

        $route = $route->middleware(TestMiddleware1::class, TestMiddleware2::class);

        $this->assertSame(
            [TestMiddleware3::class, TestMiddleware1::class,  TestMiddleware2::class],
            $route->getData('enabledMiddlewares')
        );
    }

    public function testDisableMiddlewareAfterGetEnabledMiddlewares(): void
    {
        $route = Route::get('/')
            ->middleware(TestMiddleware1::class, TestMiddleware2::class, TestMiddleware3::class);

        $route->getData('enabledMiddlewares');

        $route = $route->disableMiddleware(TestMiddleware1::class, TestMiddleware2::class);

        $this->assertSame(
            [TestMiddleware3::class],
            $route->getData('enabledMiddlewares')
        );
    }

    public function testGetEnabledMiddlewaresTwice(): void
    {
        $route = Route::get('/')
            ->middleware(TestMiddleware1::class, TestMiddleware2::class);

        $result1 = $route->getData('enabledMiddlewares');
        $result2 = $route->getData('enabledMiddlewares');

        $this->assertSame([TestMiddleware1::class, TestMiddleware2::class], $result1);
        $this->assertSame($result1, $result2);
    }

    public function testMiddlewaresWithKeys(): void
    {
        $route = Route::get('/')
            ->middleware(m3: TestMiddleware3::class)
            ->action([TestController::class, 'index'])
            ->prependMiddleware(m1: TestMiddleware1::class, m2: TestMiddleware2::class)
            ->disableMiddleware(m1: TestMiddleware1::class);

        $this->assertSame(
            [TestMiddleware2::class, TestMiddleware3::class, [TestController::class, 'index']],
            $route->getData('enabledMiddlewares')
        );
    }

    public function testDebugInfo(): void
    {
        $route = Route::get('/')
            ->name('test')
            ->host('example.com')
            ->defaults(['age' => 42])
            ->override()
            ->middleware(TestMiddleware1::class, TestMiddleware2::class)
            ->disableMiddleware(TestMiddleware2::class)
            ->action('go')
            ->prependMiddleware(TestMiddleware3::class);

        $expected = <<<EOL
Yiisoft\Router\Route Object
(
    [name] => test
    [methods] => Array
        (
            [0] => GET
        )

    [pattern] => /
    [hosts] => Array
        (
            [0] => example.com
        )

    [defaults] => Array
        (
            [age] => 42
        )

    [override] => 1
    [actionAdded] => 1
    [middlewares] => Array
        (
            [0] => Yiisoft\Router\Tests\Support\TestMiddleware3
            [1] => Yiisoft\Router\Tests\Support\TestMiddleware1
            [2] => Yiisoft\Router\Tests\Support\TestMiddleware2
            [3] => go
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
        $route = Route::get('/')->hosts('a.com', 'b.com', 'a.com');

        $this->assertSame(['a.com', 'b.com'], $route->getData('hosts'));
    }

    public function testImmutability(): void
    {
        $route = Route::get('/');
        $routeWithAction = $route->action('');

        $this->assertNotSame($route, $route->name(''));
        $this->assertNotSame($route, $route->pattern(''));
        $this->assertNotSame($route, $route->host(''));
        $this->assertNotSame($route, $route->hosts(''));
        $this->assertNotSame($route, $route->override());
        $this->assertNotSame($route, $route->defaults([]));
        $this->assertNotSame($route, $route->middleware());
        $this->assertNotSame($route, $route->action(''));
        $this->assertNotSame($routeWithAction, $routeWithAction->prependMiddleware());
        $this->assertNotSame($route, $route->disableMiddleware(''));
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }

    private function getDispatcher(ContainerInterface $container = null): MiddlewareDispatcher
    {
        if ($container === null) {
            return new MiddlewareDispatcher(
                new MiddlewareFactory($this->getContainer()),
                $this->createMock(EventDispatcherInterface::class)
            );
        }

        return new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }
}
