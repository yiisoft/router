<?php

declare(strict_types=1);

namespace Yiisoft\Router\Dispatcher;

trait DispatcherAwareTrait
{
    private ?DispatcherInterface $dispatcher = null;

    public function getDispatcher(): ?DispatcherInterface
    {
        return $this->dispatcher;
    }

    public function withDispatcher(DispatcherInterface $dispatcher): DispatcherAwareInterface
    {
        $new = clone $this;
        $new->dispatcher = $dispatcher;
        return $new;
    }
}
