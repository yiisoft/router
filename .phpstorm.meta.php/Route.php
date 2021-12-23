<?php

namespace PHPSTORM_META {

    use Yiisoft\Router\Route;

    expectedArguments(Route::getData(), 0, argumentsSet('routeDataKeys'));

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