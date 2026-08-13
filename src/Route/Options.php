<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Http\Method;

final class Options extends MethodRoute
{
    protected const METHOD = Method::OPTIONS;
}
