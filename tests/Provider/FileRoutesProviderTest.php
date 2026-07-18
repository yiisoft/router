<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Provider\FileRoutesProvider;
use RuntimeException;

use function dirname;

class FileRoutesProviderTest extends TestCase
{
    private array $routes = [];
    private string $file = __DIR__ . '/../Support/resources/routes.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->routes = require $this->file;
    }

    public function testGetRoutes(): void
    {
        $provider = new FileRoutesProvider($this->file);

        $this->assertEquals($this->routes, $provider->getRoutes());
    }

    public function testGetRoutesInDirectory(): void
    {
        $provider = new FileRoutesProvider(dirname($this->file));

        $this->assertEquals($this->routes, $provider->getRoutes());
    }

    public function testGetRoutesWithNotExistFile(): void
    {
        $file = __DIR__ . '/wrong.php';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to provide routes from "' . $file . '". File or directory not found.');

        $provider = new FileRoutesProvider($file);
        $provider->getRoutes();
    }

    public function testGetRoutesWithInvalidRoutes(): void
    {
        $file = dirname(__DIR__) . '/Support/resources/foo.php';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to provide routes from "' . $file . '". File must return an array of Route or Group instances.');

        $provider = new FileRoutesProvider($file);
        $provider->getRoutes();
    }

    public function testGetRoutesWithScope(): void
    {
        $file = dirname(__DIR__) . '/Support/resources/scope/scope_routes.php';

        $provider = new FileRoutesProvider($file, ['prefix' => '/api']);
        $routes = $provider->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('/api/test', $routes[0]->getData('pattern'));
    }

    public function testGetRoutesInDirectoryWithNonPhpFiles(): void
    {
        $dir = dirname(__DIR__) . '/Support/resources/mixed_dir';

        $provider = new FileRoutesProvider($dir);
        $routes = $provider->getRoutes();

        $this->assertCount(1, $routes);
        $this->assertSame('/mixed', $routes[0]->getData('pattern'));
    }
}
