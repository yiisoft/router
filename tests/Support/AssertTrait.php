<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

trait AssertTrait
{
    /**
     * Asserts that two strings equality ignoring line endings.
     */
    protected function assertSameStringsIgnoringLineEndingsAndSpaces(
        string $expected,
        string $actual,
        string $message = ''
    ): void {
        $expected = self::normalizeLineEndings($expected);
        $actual = self::normalizeLineEndings($actual);

        $this->assertSame($expected, $actual, $message);
    }

    private static function normalizeLineEndings(string $value): string
    {
        $value = strtr($value, [
            "\r\n" => "\n",
            "\r" => "\n",
        ]);

        return preg_replace('~\s*\n\s*~', "\n", $value);
    }
}
