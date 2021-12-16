<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
