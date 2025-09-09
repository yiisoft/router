<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Middleware;

use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\MethodFailureAction;

final class MethodFailureActionTest extends TestCase
{
    public function testShouldReturnCode204(): void
    {
        $response = $this
            ->createHandler()
            ->handle($this->createRequest(Method::OPTIONS), ['GET', 'HEAD']);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    public function testShouldReturnCode405(): void
    {
        $response = $this
            ->createHandler()
            ->handle($this->createRequest(Method::POST), ['GET', 'HEAD']);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    public function testThrownExceptionWithEmptyMethods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Allowed methods can't be empty array.");

        $response = $this
            ->createHandler()
            ->handle($this->createRequest(), []);
    }

    private function createHandler(): MethodFailureAction
    {
        return new MethodFailureAction(new Psr17Factory());
    }

    private function createRequest(string $method = Method::GET, string $uri = '/'): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }
}
