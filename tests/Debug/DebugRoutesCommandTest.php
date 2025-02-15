<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Debug;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Yiisoft\Router\Debug\DebugRoutesCommand;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\Tests\Support\TestController;
use Yiisoft\Router\Tests\Support\TestMiddleware1;
use Yiisoft\Yii\Debug\Debugger;
use Yiisoft\Yii\Debug\Storage\MemoryStorage;

final class DebugRoutesCommandTest extends TestCase
{
    public function testBase(): void
    {
        $command = new DebugRoutesCommand(
            new RouteCollection(
                (new RouteCollector())->addRoute(
                    Route::get('/')
                        ->host('example.com')
                        ->defaults(['SpecialArg' => 1])
                        ->action(fn () => 'Hello, XXXXXX!')
                        ->name('site/index'),
                    Route::get('/about')
                        ->action([TestController::class, 'index'])
                        ->name('site/about'),
                ),
            ),
            new Debugger(
                new MemoryStorage(),
                [],
            ),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('site/index', $output);
        $this->assertStringContainsString('SpecialArg', $output);
        $this->assertStringContainsString('example.com', $output);
        $this->assertStringContainsString('XXXXXX', $output);
        $this->assertStringContainsString('site/about', $output);
        $this->assertStringContainsString(TestController::class . '::index', $output);
    }

    public function testSpecificRoute(): void
    {
        $command = new DebugRoutesCommand(
            new RouteCollection(
                (new RouteCollector())->addRoute(
                    Route::get('/')
                        ->host('example.com')
                        ->defaults(['SpecialArg' => 1])
                        ->name('site/index')
                        ->middleware(TestMiddleware1::class)
                        ->action(fn () => 'Hello world!'),
                    Route::get('/about')->name('site/about'),
                ),
            ),
            new Debugger(
                new MemoryStorage(),
                [],
            ),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute(['route' => ['site/index']]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('site/index', $output);
        $this->assertStringContainsString('TestMiddleware1', $output);
        $this->assertStringContainsString('SpecialArg', $output);
        $this->assertStringContainsString('example.com', $output);
        $this->assertStringNotContainsString('site/about', $output);
    }
}
