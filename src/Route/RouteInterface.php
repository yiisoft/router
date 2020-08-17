<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Router\Dispatcher\DispatcherAwareInterface;
use Yiisoft\Router\Handler\HandlerAwareInterface;

interface RouteInterface extends \Serializable, HandlerAwareInterface, DispatcherAwareInterface
{
    public function getDefinition(): DefinitionInterface;
}
