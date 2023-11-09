<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Support;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\UrlMatcherInterface;

final class UrlMatcherStub implements UrlMatcherInterface
{
    public function __construct(
        private MatchingResult $result
    ) {
    }

    public function match(ServerRequestInterface $request): MatchingResult
    {
        return $this->result;
    }
}
