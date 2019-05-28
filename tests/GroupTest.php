<?php


namespace Yiisoft\Router\Tests;


use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

class GroupTest extends TestCase
{
    public function testGetRoutes(): void
    {
        $apiRoutes = (new Group('/api'))
            ->addRoute(Route::get('/posts'))
            ->addRoute(Route::get('/comments'));


        foreach ($apiRoutes->getRoutes() as $route) {
            /* @var Route $route */
            $this->assertStringStartsWith('/api', $route->getPattern());
        }
    }
}
