<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class TestController
{
    public function index(): ResponseInterface
    {
        return new Response();
    }
}
