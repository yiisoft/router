<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Http\Method;

final class Get extends MethodRoute
{
    protected const METHOD = Method::GET;
}
