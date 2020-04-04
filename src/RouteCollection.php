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

    /**
     * RouteCollection constructor.
     * @param RouteCollectorInterface $collector
     * @param string $prefix
     * @param bool $buildRoutes
     */
    public function __construct(RouteCollectorInterface $collector)
    {
        if ($collector instanceof Group && !empty($collector->getMiddlewares())) {
            throw new InvalidArgumentException('Collector can\'t have middlewares');
        }
        $this->collector = $collector;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        $this->ensureItemsInjected();
        return $this->routes;
    }

    /**
     * @param string $name
     * @return Route
     */
    public function getRoute(string $name): Route
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
            $this->injectItems($this->collector->getItems());
        }
    }

    /**
     * Build routes array
     *
     * @param Route[]|Group[] $items
     */
    private function injectItems(array $items): void
    {
        foreach ($items as $index => $item) {
            $this->injectItem($item);
        }
    }

    /**
     * Add an item into routes array
     * @param Route|Group $route
     */
    private function injectItem($route): void
    {
        if ($route instanceof Group) {
            $this->injectGroup($route, $this->items);
            return;
        }

        $this->items[] = $route->getName();
        $routeName = $route->getName();
        if (isset($this->routes[$routeName])) {
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
        /** @var $items Group[]|Route[] */
        $items = $group->getItems();
        foreach ($items as $index => $item) {
            $middlewares = $group->getMiddlewares();
            foreach ($middlewares as $middleware) {
                $item = $item->addMiddleware($middleware[0], $middleware[1]);
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

            if (empty($tree[$group->getPrefix()])) {
                $tree[] = $item->getName();
            } else {
                $tree[$group->getPrefix()][] = $item->getName();
            }

            /** @var Route $modifiedItem */
            $modifiedItem = $item->pattern($prefix . $item->getPattern());

            $routeName = $modifiedItem->getName();
            if (isset($this->routes[$routeName])) {
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
