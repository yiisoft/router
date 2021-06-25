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
     * @var RouteInterface[]
     */
    private array $routes = [];

    public function __construct(RouteCollectorInterface $collector)
    {
        $this->collector = $collector;
    }

    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        $this->ensureItemsInjected();
        return $this->routes;
    }

    /**
     * @param string $name
     *
     * @return RouteInterface
     */
    public function getRoute(string $name): RouteInterface
    {
        $this->ensureItemsInjected();
        if (!array_key_exists($name, $this->routes)) {
            throw new RouteNotFoundException($name);
        }

        return $this->routes[$name];
    }

    /**
     * Returns routes tree array
     *
     * @param bool $routeAsString
     *
     * @return array
     */
    public function getRouteTree(bool $routeAsString = true): array
    {
        $this->ensureItemsInjected();
        return $this->buildTree($this->items, $routeAsString);
    }

    private function ensureItemsInjected(): void
    {
        if ($this->items === []) {
            $this->injectItems([$this->collector]);
        }
    }

    /**
     * Build routes array
     *
     * @param Group[]|RouteInterface[]|RouteCollectorInterface[] $items
     */
    private function injectItems(array $items): void
    {
        foreach ($items as $index => $item) {
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
    private function injectGroup(Group $group, array &$tree, string $prefix = ''): void
    {
        $prefix .= $group->getPrefix();
        /** @var $items Group[]|RouteInterface[] */
        $items = $group->getItems();
        foreach ($items as $item) {
            if ($item instanceof Group || $item->hasMiddlewares()) {
                $groupMiddlewares = $group->getMiddlewareDefinitions();
                foreach ($groupMiddlewares as $middleware) {
                    $item = $item->prependMiddleware($middleware);
                }
            }

            if ($item instanceof Group) {
                if (empty($item->getPrefix())) {
                    $this->injectGroup($item, $tree, $prefix);
                    continue;
                }
                $tree[$item->getPrefix()] = [];
                $this->injectGroup($item, $tree[$item->getPrefix()], $prefix);
                continue;
            }

            /** @var Route $modifiedItem */
            $modifiedItem = $item->pattern($prefix . $item->getPattern());

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
