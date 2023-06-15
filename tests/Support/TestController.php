<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\Attribute\Get;
use Yiisoft\Router\Group;

#[Group('/test')]
final class TestController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(200, [], $request->getAttribute('content', ''));
    }

    #[Get('/')]
    public function attributeAction(): Response
    {
        return new Response(200, [], 'test');
    }
}
