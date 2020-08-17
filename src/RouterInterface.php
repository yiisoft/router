<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Dispatcher\DispatcherAwareInterface;

interface RouterInterface extends RequestHandlerInterface, MatcherInterface, DispatcherAwareInterface
{
}
