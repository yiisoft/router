<?php

declare(strict_types=1);

namespace Yiisoft\Router\Internal;

use function in_array;
use function rtrim;

/**
 * @internal
 */
final class HostNormalizer
{
    /**
     * @param string[] $hosts
     *
     * @return string[]
     */
    public static function normalize(array $hosts): array
    {
        $result = [];

        foreach ($hosts as $host) {
            $host = rtrim($host, '/');

            if ($host !== '' && !in_array($host, $result, true)) {
                $result[] = $host;
            }
        }

        return $result;
    }
}
