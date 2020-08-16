<?php

declare(strict_types=1);

namespace Yiisoft\Router\Interfaces;

use Psr\Http\Server\RequestHandlerInterface;

interface RouterInterface extends RequestHandlerInterface, MatcherInterface, DispatcherAwareInterface
{

}
