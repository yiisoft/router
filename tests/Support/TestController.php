<?php

namespace Yiisoft\Router\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * ETestController
 */
class TestController
{
    /**
     * @return string
     */
    public function index(): ResponseInterface
    {
        return new Response();
    }
}
