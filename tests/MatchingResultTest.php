<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;

final class MatchingResultTest extends TestCase
{
    public function testFromSuccess(): void
    {
        $route = Route::get('/{name}');

        $result = MatchingResult::fromSuccess($route, ['name' => 'Mehdi']);
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['name' => 'Mehdi'], $result->arguments());
    }

    public function testFromFailureOnMethodFailure(): void
    {
        $result = MatchingResult::fromFailure([Method::GET, Method::HEAD]);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isMethodFailure());
        $this->assertSame([Method::GET, Method::HEAD], $result->methods());
    }

    public function testFromFailureOnNotFoundFailure(): void
    {
        $result = MatchingResult::fromFailure(Method::ALL);

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isMethodFailure());
    }

    public function testRouteOnFailure(): void
    {
        $result = MatchingResult::fromFailure([Method::GET, Method::HEAD]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('There is no route in the matching result.');
        $result->route();
    }
}
