<?php

declare(strict_types=1);

namespace Yiisoft\Router\Resource;

use olvlvl\ComposerAttributeCollector\Attributes;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * An attribute resource represents routes that declared via PHP Attributes.
 * @codeCoverageIgnore
 */
final class AttributeResource implements ResourceInterface
{
    /**
     * @var array<class-string, \ReflectionMethod>
     */
    private static array $reflectionsCache = [];

    public function getRoutes(): array
    {
        $routes = [];
        $groupRoutes = [];
        $predicate = Attributes::predicateForAttributeInstanceOf(Route::class);
        $targetMethods = Attributes::filterTargetMethods($predicate);
        foreach ($targetMethods as $targetMethod) {
            /** @var Route $route */
            $route = $targetMethod->attribute;
            $targetMethodReflection = self::$reflectionsCache[$targetMethod->class] ??= new \ReflectionMethod(
                $targetMethod->class,
                $targetMethod->name
            );
            /** @var Group[] $groupAttributes */
            $groupAttributes = $targetMethodReflection->getAttributes(
                Group::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );
            if (!empty($groupAttributes)) {
                $groupRoutes[$targetMethod->class][] = $route->action([$targetMethod->class, $targetMethod->name]);
            } else {
                $routes[] = $route->action([$targetMethod->class, $targetMethod->name]);
            }
        }
        $predicate = static fn (string $attribute): bool => is_a($attribute, Route::class, true)
            || is_a($attribute, Group::class, true);
        $targetClasses = Attributes::filterTargetClasses($predicate);
        foreach ($targetClasses as $targetClass) {
            if (isset($groupRoutes[$targetClass->name])) {
                /** @var Group $group */
                $group = $targetClass->attribute;
                $routes[] = $group->routes(...$groupRoutes[$targetClass->name]);
            } else {
                /** @var Route $group */
                $routes[] = $group->action($targetClass->name);
            }
        }
        return $routes;
    }
}
