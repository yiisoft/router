<?php

declare(strict_types=1);

namespace Yiisoft\Router\Provider;

use olvlvl\ComposerAttributeCollector\Attributes;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * An attribute provider provides routes that declared via PHP Attributes.
 * Currently, uses `olvlvl/composer-attribute-collector`. {@link https://github.com/olvlvl/composer-attribute-collector}.
 * @codeCoverageIgnore
 */
final class AttributeRoutesProvider implements RoutesProviderInterface
{
    /**
     * @var array<class-string, \ReflectionMethod>
     */
    private static array $reflectionsCache = [];

    public function getRoutes(): array
    {
        $routes = [];
        $groupRoutes = [];
        $routePredicate = Attributes::predicateForAttributeInstanceOf(Route::class);
        $targetMethods = Attributes::filterTargetMethods($routePredicate);
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
        $groupPredicate = static fn (string $attribute): bool => is_a($attribute, Route::class, true)
            || is_a($attribute, Group::class, true);
        $targetClasses = Attributes::filterTargetClasses($groupPredicate);
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
