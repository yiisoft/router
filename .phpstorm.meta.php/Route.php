<?php

namespace PHPSTORM_META {

    expectedArguments(\Yiisoft\Router\Route::getData(), 0, argumentsSet('routeDataKeys'));

    registerArgumentsSet(
        'routeDataKeys',
        'name',
        'host',
        'pattern',
        'methods',
        'override',
        'defaults'
    );
}