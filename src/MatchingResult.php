<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

final class MatchingResult
{
    /**
     * @var array<string,string>
     */
    private array $arguments = [];

    /**
     * @var string[]
     */
    private array $methods = [];

    private function __construct(private ?Route $route)
    {
    }

    /**
     * @param array<string,string> $arguments
     */
    public static function fromSuccess(Route $route, array $arguments): self
    {
        $new = new self($route);
        $new->arguments = $arguments;
        return $new;
    }

    /**
     * @param string[] $methods
     */
    public static function fromFailure(array $methods): self
    {
        $new = new self(null);
        $new->methods = $methods;
        return $new;
    }

    /**
     * @psalm-assert-if-true !null $this->route
     */
    public function isSuccess(): bool
    {
        return $this->route !== null;
    }

    public function isMethodFailure(): bool
    {
        return $this->route === null && $this->methods !== Method::ALL;
    }

    /**
     * @return array<string,string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string[]
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * @psalm-assert-if-true true $this->isSuccess()
     */
    public function route(): Route
    {
        if ($this->route === null) {
            throw new RuntimeException('There is no route in the matching result.');
        }

        return $this->route;
    }
}
