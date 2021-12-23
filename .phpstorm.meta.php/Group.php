<?php

namespace PHPSTORM_META {

    use Yiisoft\Router\Group;

    expectedArguments(Group::getData(), 0, argumentsSet('groupDataKeys'));

    registerArgumentsSet(
        'groupDataKeys',
        'prefix',
        'namePrefix',
        'host',
        'corsMiddleware',
        'items'
    );
}