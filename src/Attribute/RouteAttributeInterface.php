<?php

declare(strict_types=1);

namespace Yiisoft\Router\Attribute;

use Yiisoft\Router\Route;

interface RouteAttributeInterface
{
    public function getRoute(): Route;
}
