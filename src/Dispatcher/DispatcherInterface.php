<?php

declare(strict_types=1);

namespace Yiisoft\Router\Dispatcher;

use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Handler\HandlerAwareInterface;

interface DispatcherInterface extends RequestHandlerInterface, HandlerAwareInterface
{
}
