<?php

declare(strict_types=1);

use Yiisoft\Router\Debug\DebugRoutesCommand;
use Yiisoft\Router\Debug\RouterCollector;
use Yiisoft\Router\Debug\UrlMatcherInterfaceProxy;
use Yiisoft\Router\UrlMatcherInterface;

return [
    'yiisoft/yii-debug' => [
        'collectors.web' => [
            RouterCollector::class,
        ],
        'trackedServices' => [
            UrlMatcherInterface::class => [UrlMatcherInterfaceProxy::class, RouterCollector::class],
        ],
    ],
];
