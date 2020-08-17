<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Http\Method;
use Yiisoft\Router\Dispatcher\DispatcherAwareInterface;
use Yiisoft\Router\Dispatcher\DispatcherAwareTrait;
use Yiisoft\Router\Dispatcher\DispatcherInterface;
use Yiisoft\Router\Handler\HandlerAwareTrait;

/**
 * Class Route
 *
 * @method static DispatcherAwareInterface withDispatcher(DispatcherInterface $dispatcher)
 */
final class Route implements RouteInterface
{
    use DispatcherAwareTrait;
    use HandlerAwareTrait;

    private DefinitionInterface $definition;

    private function __construct(DefinitionInterface $definition)
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

    public function getDefinition(): DefinitionInterface
    {
        return $this->definition;
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
        $definition = new Definition($path, $methods);

        return (new self($definition))->handler($handler);
    }

    public function name(string $name): self
    {
        $route = clone $this;
        $route->definition = $route->definition->withName($name);

        return $route;
    }

    // TODO: add other methods to build definition
}
