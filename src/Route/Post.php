<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Http\Method;

final class Post extends MethodRoute
{
    protected const METHOD = Method::POST;
}
