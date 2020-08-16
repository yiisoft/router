<?php

declare(strict_types=1);

namespace Yiisoft\Router\Interfaces;

interface DispatcherAwareInterface
{
    public function getDispatcher(): ?DispatcherInterface;
}
