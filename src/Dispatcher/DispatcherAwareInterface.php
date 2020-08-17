<?php

declare(strict_types=1);

namespace Yiisoft\Router\Dispatcher;

interface DispatcherAwareInterface
{
    public function getDispatcher(): ?DispatcherInterface;

    public function withDispatcher(DispatcherInterface $dispatcher): DispatcherAwareInterface;
}
