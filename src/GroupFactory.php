<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

final class GroupFactory
{
    public function __invoke(ContainerInterface $container): Group
    {
        return Group::create(null, null, $container);
    }
}
