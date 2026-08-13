<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Http\Method;

final class Delete extends MethodRoute
{
    protected const METHOD = Method::DELETE;
}
