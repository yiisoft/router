<?php


namespace Yiisoft\Router\Tests\Support;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \RuntimeException implements NotFoundExceptionInterface
{
}
