<?php

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Route::get(''),
    Group::create('')->routes(Route::get('/blog')),
];
