<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

use BackedEnum;
use Stringable;
use Yiisoft\Router\EnumRouteNameUrlGeneratorInterface;

final class EnumRouteNameUrlGeneratorStub implements EnumRouteNameUrlGeneratorInterface
{
    private string $uriPrefix = '';

    public function generate(
        string|BackedEnum $name,
        array $arguments = [],
        array $queryParameters = [],
        ?string $hash = null,
    ): string {
        return '/' . $this->resolveName($name);
    }

    public function generateAbsolute(
        string|BackedEnum $name,
        array $arguments = [],
        array $queryParameters = [],
        ?string $hash = null,
        ?string $scheme = null,
        ?string $host = null,
    ): string {
        return 'https://example.com/' . $this->resolveName($name);
    }

    public function generateFromCurrent(
        array $replacedArguments,
        array $queryParameters = [],
        ?string $hash = null,
        string|BackedEnum|null $fallbackRouteName = null,
    ): string {
        return '/' . ($fallbackRouteName === null ? 'current' : $this->resolveName($fallbackRouteName));
    }

    public function getUriPrefix(): string
    {
        return $this->uriPrefix;
    }

    public function setUriPrefix(string $name): void
    {
        $this->uriPrefix = $name;
    }

    public function setDefaultArgument(string $name, bool|float|int|string|Stringable|null $value): void {}

    private function resolveName(string|BackedEnum $name): string
    {
        return $name instanceof BackedEnum ? (string) $name->value : $name;
    }
}
