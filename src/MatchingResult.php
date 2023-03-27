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

final class MatchingResult implements MiddlewareInterface
{
    /**
     * @var array<string,string>
     */
    private array $arguments = [];

    /**
     * @var string[]
     */
    private array $methods = [];

    private ?MiddlewareDispatcher $dispatcher = null;

    private function __construct(private ?Route $route)
    {
    }

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self
    {
        $new = clone $this;
        $new->dispatcher = $dispatcher;
        return $new;
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

    public function route(): Route
    {
        if ($this->route === null) {
            throw new RuntimeException('There is no route in the matching result.');
        }

        return $this->route;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isSuccess() && $this->dispatcher !== null) {
            return $this
                ->buildDispatcher($this->dispatcher, $this->route)
                ->dispatch($request, $handler);
        }

        return $handler->handle($request);
    }

    private function buildDispatcher(
        ?MiddlewareDispatcher $dispatcher,
        Route $route,
    ): MiddlewareDispatcher {
        if ($dispatcher === null) {
            throw new RuntimeException(sprintf('There is no dispatcher in the route %s.', $route->getData('name')));
        }

        // Don't add middlewares to dispatcher if we did it earlier.
        // This improves performance in event-loop applications.
        if ($dispatcher->hasMiddlewares()) {
            return $dispatcher;
        }

        return $dispatcher->withMiddlewares($route->getMiddlewares());
    }
}
