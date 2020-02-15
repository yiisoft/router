<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;

final class RouteCollection implements RouteCollectionInterface
{
    private ?string $prefix = null;

    /**
     * @var array
     */
    private array $items = [];

    /**
     * All attached routes as Route instances
     *
     * @var Route[]
     */
    private array $routes = [];

    /**
     * RouteCollection constructor.
     * @param RouteCollectorInterface $collector
     * @param string $prefix
     * @param bool $buildRoutes
     */
    public function __construct(?RouteCollectorInterface $collector, string $prefix = null, bool $buildRoutes = true)
    {
        $this->prefix = $prefix;
        if ($collector !== null) {
            $this->injectItems([$collector], $buildRoutes);
        }
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return Route|RouteCollection[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param string $name
     * @return Route
     */
    public function getRoute(string $name): Route
    {
        if (!array_key_exists($name, $this->routes)) {
            throw new RouteNotFoundException($name);
        }

        return $this->routes[$name];
    }

    /**
     * Returns routes tree array
     *
     * @return array
     */
    public function getRouteTree(): array
    {
        return $this->buildRouteTree($this);
    }

    /**
     * Build routes array
     *
     * @param Route|Group[] $items
     */
    private function injectItems(array $items, bool $buildRoutes): void
    {
        foreach ($items as $index => $item) {
            $this->injectItem($item, $buildRoutes);
        }
    }

    /**
     * Add an item into routes array
     * @param Route|Group $route
     */
    private function injectItem($route, bool $buildRoutes): void
    {
        if ($route instanceof Group) {
            $this->injectGroup($route, $buildRoutes, $this);
            return;
        }
        $this->items[] = $route;
        if ($buildRoutes) {
            $this->routes[$route->getName()] = $route;
        }
    }

    /**
     * Inject a Group instance into route and item arrays.
     */
    private function injectGroup(Group $group, bool $buildRoutes, ?RouteCollection $collection, string $prefix = ''): void
    {
        $prefix .= $group->getPrefix();
        /** @var $items Group[]|Route[] */
        $items = $group->getItems();
        foreach ($items as $index => $item) {
            if ($item instanceof Group) {

                if (empty($item->getPrefix())) {
                    $newCollection = $collection;
                } else {
                    $newCollection = new self(null, $item->getPrefix(), false);
                    $collection->items[] = $newCollection;
                }

                $this->injectGroup($item, $buildRoutes, $newCollection, $prefix);
                continue;
            }

            $collection->items[] = $item;
            if (!$buildRoutes) {
                continue;
            }
            /** @var Route $modifiedItem */
            $modifiedItem = $item->pattern($prefix . $item->getPattern());

            $groupMiddlewares = $group->getMiddlewares();

            for (end($groupMiddlewares); key($groupMiddlewares) !== null; prev($groupMiddlewares)) {
                $modifiedItem = $modifiedItem->addMiddleware(current($groupMiddlewares));
            }

            $routeName = $modifiedItem->getName();
            if (isset($this->routes[$routeName])) {
                throw new InvalidArgumentException("A route with name '$routeName' already exists.");
            }
            $this->routes[$routeName] = $modifiedItem;
        }
    }

    /**
     * Builds route tree from collection recursive
     *
     * @param RouteCollection $collection
     * @return array
     */
    private function buildRouteTree(RouteCollection $collection): array
    {
        $tree = [];
        $items = $collection->getItems();
        foreach ($items as $item) {
            if ($item instanceof Route) {
                $tree[] = (string)$item;
            }
            if ($item instanceof RouteCollection) {
                $tree[$item->getPrefix()] = $this->buildRouteTree($item);
            }
        }

        return $tree;
    }
}
