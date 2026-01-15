<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

use Psr\Container\ContainerInterface;

use function array_key_exists;

final class Container implements ContainerInterface
{
    public function __construct(private array $instances) {}

    public function get($id)
    {
        if ($this->has($id)) {
            return $this->instances[$id];
        }

        throw new NotFoundException("$id was not found in container");
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances);
    }
}
