<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Route::get(''),
    Group::create('')->routes(Route::get('/blog')),
];
