<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Middleware;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\MethodNotAllowedHandler;

final class MethodNotAllowedHandlerTest extends TestCase
{
    public function testShouldReturnCode405(): void
    {
        $response = $this
            ->createHandler()
            ->withAllowedMethods(['GET', 'HEAD'])
            ->handle($this->createRequest());

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    public function testThrownExceptionWithEmptyMethods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Allowed methods can't be empty array.");

        $response = $this
            ->createHandler()
            ->withAllowedMethods([])
            ->handle($this->createRequest());
    }

    private function createHandler(): MethodNotAllowedHandler
    {
        return new MethodNotAllowedHandler(new Psr17Factory());
    }

    private function createRequest(string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest(Method::GET, $uri);
    }
}
