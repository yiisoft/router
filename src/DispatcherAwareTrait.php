<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Router\Interfaces\DispatcherInterface;

trait DispatcherAwareTrait
{
    private ?DispatcherInterface $dispatcher;

    public function getDispatcher(): ?DispatcherInterface
    {
        return $this->dispatcher;
    }
}
