<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Middleware;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\AllowedMethodsHandler;

final class AllowedMethodsHandlerTest extends TestCase
{
    public function testShouldReturnCode204(): void
    {
        $response = $this
            ->createHandler()
            ->withAllowedMethods(['GET', 'HEAD'])
            ->handle($this->createRequest());

        $this->assertSame(204, $response->getStatusCode());
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

    private function createHandler(): AllowedMethodsHandler
    {
        return new AllowedMethodsHandler(new Psr17Factory());
    }

    private function createRequest(string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest(Method::GET, $uri);
    }
}
