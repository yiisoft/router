<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface GroupParametersInterface
{
    /**
     * @return Group[]|Route[]
     */
    public function getItems(): array;

    public function getPrefix(): ?string;

    public function getMiddlewareDefinitions(): array;

    public function getHost(): ?string;

    public function getName(): ?string;
}
