<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Provider\FileRoutesProvider;

class FileResourceTest extends TestCase
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

    public function testGetRoutesWithNotExistFile(): void
    {
        $file = __DIR__ . '/foo.php';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to provide routes from "' . $file . '". File or directory not found.');

        $provider = new FileRoutesProvider($file);
        $provider->getRoutes();
    }

    public function testGetRoutesInDirectory(): void
    {
        $provider = new FileRoutesProvider(dirname($this->file));

        $this->assertEquals($this->routes, $provider->getRoutes());
    }
}
