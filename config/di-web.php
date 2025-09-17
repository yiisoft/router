<?php

declare(strict_types=1);

use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\MethodFailureHandler;
use Yiisoft\Router\MethodFailureHandlerInterface;

return [
    CurrentRoute::class => [
        'reset' => function () {
            $this->route = null;
            $this->uri = null;
            $this->arguments = [];
        },
    ],
    MethodFailureHandlerInterface::class => MethodFailureHandler::class,
];
