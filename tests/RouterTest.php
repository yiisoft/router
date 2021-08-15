<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Uri;
use Yiisoft\Router\Route;
use Yiisoft\Router\Router;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testGetCurrentRoute()
    {
        $route = Route::get('')->name('test');
        $router = new Router();
        $router->setCurrentRoute($route);

        $this->assertSame($route, $router->getCurrentRoute());
    }

    public function testGetCurrentUri()
    {
        $uri = new Uri('/test');
        $router = new Router();
        $router->setCurrentUri($uri);

        $this->assertSame($uri, $router->getCurrentUri());
    }
}
