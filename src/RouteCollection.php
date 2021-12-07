<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;

final class RouteCollection implements RouteCollectionInterface
{
    private RouteCollectorInterface $collector;

    private array $items = [];

    /**
     * All attached routes as Route instances
     *
     * @var Route[]
     */
    private array $routes = [];

    public function __construct(RouteCollectorInterface $collector)
    {
        $this->collector = $collector;
    }

    public function getRoutes(): array
    {
        $this->ensureItemsInjected();
        return $this->routes;
    }

    public function getRoute(string $name): Route
    {
        $this->ensureItemsInjected();
        if (!array_key_exists($name, $this->routes)) {
            throw new RouteNotFoundException($name);
        }

        return $this->routes[$name];
    }

    public function getRouteTree(bool $routeAsString = true): array
    {
        $this->ensureItemsInjected();
        return $this->buildTree($this->items, $routeAsString);
    }

    private function ensureItemsInjected(): void
    {
        if ($this->items === []) {
            $this->injectItems($this->collector->getItems());
        }
    }

    /**
     * Build routes array
     *
     * @param Group[]|Route[] $items
     */
    private function injectItems(array $items): void
    {
        foreach ($items as $index => $item) {
            foreach ($this->collector->getMiddlewareDefinitions() as $middlewareDefinition) {
                $item = $item->prependMiddleware($middlewareDefinition);
            }
            $this->injectItem($item);
        }
    }

    /**
     * Add an item into routes array
     *
     * @param Group|Route $route
     */
    private function injectItem($route): void
    {
        if ($route instanceof Group) {
            $this->injectGroup($route, $this->items);
            return;
        }

        $this->items[] = $route->getName();
        $routeName = $route->getName();
        if (isset($this->routes[$routeName]) && !$route->isOverride()) {
            throw new InvalidArgumentException("A route with name '$routeName' already exists.");
        }
        $this->routes[$routeName] = $route;
    }

    /**
     * Inject a Group instance into route and item arrays.
     */
    private function injectGroup(Group $group, array &$tree, string $prefix = '', string $namePrefix = ''): void
    {
        $prefix .= $group->getPrefix();
        $namePrefix .= $group->getNamePrefix();
        $items = $group->getItems();
        foreach ($items as $item) {
            if ($item instanceof Group || $item->hasMiddlewares()) {
                $groupMiddlewares = $group->getMiddlewareDefinitions();
                foreach ($groupMiddlewares as $middleware) {
                    $item = $item->prependMiddleware($middleware);
                }
            }

            if ($group->getHost() !== null && $item->getHost() === null) {
                $item = $item->host($group->getHost());
            }

            if ($group->isPreFlight()) {
                $item = $item->preFlight();
            }

            if ($item instanceof Group) {
                if (empty($item->getPrefix())) {
                    $this->injectGroup($item, $tree, $prefix, $namePrefix);
                    continue;
                }
                $tree[$item->getPrefix()] = [];
                $this->injectGroup($item, $tree[$item->getPrefix()], $prefix, $namePrefix);
                continue;
            }

            /** @var Route $modifiedItem */
            $modifiedItem = $item->pattern($prefix . $item->getPattern());

            if (strpos($modifiedItem->getName(), implode(', ', $modifiedItem->getMethods())) === false) {
                $modifiedItem = $modifiedItem->name($namePrefix . $modifiedItem->getName());
            }

            if (empty($tree[$group->getPrefix()])) {
                $tree[] = $modifiedItem->getName();
            } else {
                $tree[$group->getPrefix()][] = $modifiedItem->getName();
            }

            $routeName = $modifiedItem->getName();
            if (isset($this->routes[$routeName]) && !$modifiedItem->isOverride()) {
                throw new InvalidArgumentException("A route with name '$routeName' already exists.");
            }
            $this->routes[$routeName] = $modifiedItem;
        }
    }

    /**
     * Builds route tree from items
     *
     * @param array $items
     * @param bool $routeAsString
     *
     * @return array
     */
    private function buildTree(array $items, bool $routeAsString): array
    {
        $tree = [];
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $tree[$key] = $this->buildTree($items[$key], $routeAsString);
            } else {
                $tree[] = $routeAsString ? (string)$this->getRoute($item) : $this->getRoute($item);
            }
        }
        return $tree;
    }
}
