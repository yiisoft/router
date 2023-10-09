<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Resource;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Resource\FileResource;

class FileResourceTest extends TestCase
{
    private array $routes = [];
    private string $file = __DIR__ . '/../Support/routes.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->routes = require $this->file;
    }

    public function testGetRoutes(): void
    {
        $resource = new FileResource($this->file);

        $this->assertEquals($this->routes, $resource->getRoutes());
    }
}
