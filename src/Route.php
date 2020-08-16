<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Http\Method;
use Yiisoft\Router\Interfaces\DispatcherAwareInterface;
use Yiisoft\Router\Interfaces\DispatcherInterface;
use Yiisoft\Router\Interfaces\RouteDefinitionInterface;
use Yiisoft\Router\Interfaces\RouteInterface;

class Route implements RouteInterface
{
    use MiddlewareAwareTrait;
    private RouteDefinitionInterface $definition;
    private ?DispatcherInterface $dispatcher = null;

    private function __construct(RouteDefinitionInterface $definition)
    {
        $this->definition = $definition;
    }

    public function serialize(): string
    {
        // TODO: Implement serialize() method.
        return '';
    }

    public function unserialize($serialized)
    {
        // TODO: Implement unserialize() method.
    }

    public function getDefinition(): RouteDefinitionInterface
    {
        return $this->definition;
    }

    public function getDispatcher(): ?DispatcherInterface
    {
        return $this->dispatcher;
    }

    public function withDispatcher(?DispatcherInterface $dispatcher): self
    {
        $route = clone $this;
        $route->dispatcher = $dispatcher;
        return $route;
    }

    // TODO: Move all HTTP method factories to separate trait to make easier defining
    // own RouteInterface implementation and not writing all these methods again
    public static function get(string $path, $handler = null): self
    {
        return self::methods([Method::GET], $path, $handler);
    }

    public static function post(string $path, $handler = null): self
    {
        return self::methods([Method::POST], $path, $handler);
    }

    public static function put(string $path, $handler = null): self
    {
        return self::methods([Method::PUT], $path, $handler);
    }

    public static function delete(string $path, $handler = null): self
    {
        return self::methods([Method::DELETE], $path, $handler);
    }

    public static function patch(string $path, $handler = null): self
    {
        return self::methods([Method::PATCH], $path, $handler);
    }

    public static function head(string $path, $handler = null): self
    {
        return self::methods([Method::HEAD], $path, $handler);
    }

    public static function options(string $path, $handler = null): self
    {
        return self::methods([Method::OPTIONS], $path, $handler);
    }

    public static function anyMethod(string $path, $handler = null): self
    {
        return self::methods(Method::ANY, $path, $handler);
    }

    public static function methods(array $methods, string $path, $handler = null): self
    {
        // TODO: Do we need to validate methods? If yes, we do it here or in definition
        $definition = new RouteDefinition($path, $methods);

        return (new self($definition))->middleware($handler);
    }

    public function name(string $name): self
    {
        $route = clone $this;
        $route->definition = $route->definition->withName($name);

        return $route;
    }

    // TODO: add other methods to build definition
}
