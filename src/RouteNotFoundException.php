<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use RuntimeException;
use Throwable;

use function sprintf;

final class RouteNotFoundException extends RuntimeException
{
    public function __construct(string $routeName = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = sprintf(
            'Cannot generate URI for route "%s"; route not found.',
            $routeName,
        );
        parent::__construct($message, $code, $previous);
    }
}
