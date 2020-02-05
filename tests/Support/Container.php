<?php


namespace Yiisoft\Router\Tests\Support;


use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private array $instances;

    public function __construct(array $instances)
    {
        $this->instances = $instances;
    }

    public function get($id)
    {
        if ($this->has($id)) {
            return $this->instances[$id];
        }

        throw new NotFoundException("$id was not found in container");
    }

    public function has($id)
    {
        return array_key_exists($id, $this->instances);
    }
}
