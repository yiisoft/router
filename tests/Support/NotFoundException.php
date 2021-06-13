<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
