<?php

declare(strict_types=1);

namespace Yiisoft\Router\Interfaces;

use Psr\Http\Server\MiddlewareInterface;

interface RouteInterface extends \Serializable, MiddlewareAwareInterface, DispatcherAwareInterface
{
    public function getDefinition(): RouteDefinitionInterface;
}
