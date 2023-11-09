<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RoutableInterface
{
    public function toRoute(): Route|Group;
}
