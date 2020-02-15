<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;

final class RouteCollection implements RouteCollectionInterface
{
    private $items = [];

    /**
     * All attached routes as Route instances
     *
     * @var Route[]
     */
    private array $routes = [];

    public function __construct(RouteCollectorInterface $collector)
    {
        $this->items[] = $collector;
        $this->injectItems();
    }

    /**
     * Inject queued items into the underlying router
     */
    private function injectItems(): void
    {
        foreach ($this->items as $index => $item) {
            $this->injectItem($item);
        }
    }

    /**
     * Inject an item into the underlying router
     * @param Route|Group $route
     */
    private function injectItem($route): void
    {
        if ($route instanceof Group) {
            $this->injectGroup($route);
            return;
        }

        $this->routes[$route->getName()] = $route;
    }

    /**
     * Inject a Group instance into the underlying router.
     */
    private function injectGroup(Group $group, string $prefix = ''): void
    {
        $prefix .= $group->getPrefix();
        /** @var $items Group[]|Route[] */
        $items = $group->getItems();
        foreach ($items as $index => $item) {
            if ($item instanceof Group) {
                $this->injectGroup($item, $prefix);
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

    public function getItems(): array
    {
        //TODO make immutable collection
        return $this->items;
    }

    /**
     * @return array
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
}
