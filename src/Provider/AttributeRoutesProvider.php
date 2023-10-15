<?php

declare(strict_types=1);

namespace Yiisoft\Router\Provider;

use olvlvl\ComposerAttributeCollector\Attributes;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * An attribute provider provides routes that declared via PHP Attributes.
 * Currently, uses `olvlvl/composer-attribute-collector`. {@link https://github.com/olvlvl/composer-attribute-collector}.
 *
 * @codeCoverageIgnore
 */
final class AttributeRoutesProvider implements RoutesProviderInterface
{
    /**
     * @var array<class-string, \ReflectionClass>
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
            $targetMethodReflection = self::$reflectionsCache[$targetMethod->class] ??= new \ReflectionClass(
                $targetMethod->class
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
            $group = $targetClass->attribute;
            if ($group instanceof Group && isset($groupRoutes[$targetClass->name])) {
                $routes[] = $group->routes(...$groupRoutes[$targetClass->name]);
            } elseif ($group instanceof Route) {
                $routes[] = $group->action($targetClass->name);
            }
        }
        return $routes;
    }
}
