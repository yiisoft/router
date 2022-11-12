<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\RouteNotFoundException;

final class RouteNotFoundExceptionTest extends TestCase
{
    public function testDefaultArguments(): void
    {
        $exception = new RouteNotFoundException();

        $this->assertSame('Cannot generate URI for route ""; route not found.', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testCustomArguments(): void
    {
        $previousException = new InvalidArgumentException();

        $exception = new RouteNotFoundException('test', 213, $previousException);

        $this->assertSame('Cannot generate URI for route "test"; route not found.', $exception->getMessage());
        $this->assertSame(213, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
