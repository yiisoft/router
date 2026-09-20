<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Tests\Support\EnumRouteNameUrlGeneratorStub;
use Yiisoft\Router\Tests\Support\TestRouteName;
use Yiisoft\Router\UrlGeneratorInterface;

final class EnumRouteNameUrlGeneratorInterfaceTest extends TestCase
{
    public function testExtendsUrlGeneratorInterface(): void
    {
        $this->assertInstanceOf(UrlGeneratorInterface::class, new EnumRouteNameUrlGeneratorStub());
    }

    public function testGenerateAcceptsBackedEnumAndString(): void
    {
        $generator = new EnumRouteNameUrlGeneratorStub();

        $this->assertSame('/home', $generator->generate(TestRouteName::Home));
        $this->assertSame('/home', $generator->generate('home'));
    }

    public function testGenerateAbsoluteAcceptsBackedEnum(): void
    {
        $generator = new EnumRouteNameUrlGeneratorStub();

        $this->assertSame('https://example.com/about', $generator->generateAbsolute(TestRouteName::About));
    }

    public function testGenerateFromCurrentAcceptsBackedEnumFallback(): void
    {
        $generator = new EnumRouteNameUrlGeneratorStub();

        $this->assertSame('/home', $generator->generateFromCurrent([], fallbackRouteName: TestRouteName::Home));
        $this->assertSame('/current', $generator->generateFromCurrent([]));
    }

    public function testExistingImplementationsStayValid(): void
    {
        $generator = new class implements UrlGeneratorInterface {
            public function generate(
                string $name,
                array $arguments = [],
                array $queryParameters = [],
                ?string $hash = null,
            ): string {
                return '/' . $name;
            }

            public function generateAbsolute(
                string $name,
                array $arguments = [],
                array $queryParameters = [],
                ?string $hash = null,
                ?string $scheme = null,
                ?string $host = null,
            ): string {
                return 'https://example.com/' . $name;
            }

            public function generateFromCurrent(
                array $replacedArguments,
                array $queryParameters = [],
                ?string $hash = null,
                ?string $fallbackRouteName = null,
            ): string {
                return '/' . ($fallbackRouteName ?? 'current');
            }

            public function getUriPrefix(): string
            {
                return '';
            }

            public function setUriPrefix(string $name): void {}

            public function setDefaultArgument(string $name, mixed $value): void {}
        };

        $this->assertSame('/home', $generator->generate('home'));
    }
}
