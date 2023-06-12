<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * Provides an implementation of {@see RouteAttributesRegistrarInterface} using {@see get_declared_classes()} function.
 */
final class RouteAttributesRegistrar implements RouteAttributesRegistrarInterface
{
    public function __construct(private RouteCollectorInterface $collector)
    {
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        //TODO: caching?
        $classes = get_declared_classes();
        foreach ($classes as $class) {
            $reflectionClass = new \ReflectionClass($class);
            if (!$reflectionClass->isUserDefined()) {
                continue;
            }
            $routes = $this->getRoutes($reflectionClass);
            $groupAttributes = $reflectionClass->getAttributes(Group::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (!empty($groupAttributes)) {
                [$groupAttribute] = $groupAttributes;
                /** @var Group $group */
                $group = $groupAttribute->newInstance();
                $this->collector->addRoute($group->routes(...$routes));
            } else {
                $this->collector->addRoute(...$routes);
            }
        }
    }

    private function getRoutes(\ReflectionClass $reflectionClass): iterable
    {
        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            foreach (
                $reflectionMethod->getAttributes(
                    Route::class,
                    \ReflectionAttribute::IS_INSTANCEOF
                ) as $reflectionAttribute
            ) {
                /** @var Route $route */
                $route = $reflectionAttribute->newInstance();

                yield $route->action([$reflectionClass->getName(), $reflectionMethod->getName()]);
            }
        }
    }
}
