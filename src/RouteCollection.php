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
     *
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
            $this->injectItems($this->collector->getItems());
        }
    }

    /**
     * Build routes array
     *
     * @param Group[]|Route[]|RouteCollectorInterface[] $items
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
        $routes = $group->getRoutes();
        foreach ($routes as $route) {
            if ($route instanceof Group || $route->hasMiddlewares()) {
                $groupMiddlewares = $group->getMiddlewareDefinitions();
                foreach ($groupMiddlewares as $middleware) {
                    $route = $route->prependMiddleware($middleware);
                }
            }

            if ($group->getHost() !== null && $route->getHost() === null) {
                $route = $route->host($group->getHost());
            }

            if ($route instanceof Group) {
                if (empty($route->getPrefix())) {
                    $this->injectGroup($route, $tree, $prefix, $namePrefix);
                    continue;
                }
                $tree[$route->getPrefix()] = [];
                $this->injectGroup($route, $tree[$route->getPrefix()], $prefix, $namePrefix);
                continue;
            }

            /** @var Route $modifiedRoute */
            $modifiedRoute = $route->pattern($prefix . $route->getPattern());

            if (strpos($modifiedRoute->getName(), implode(', ', $modifiedRoute->getMethods())) === false) {
                $modifiedRoute = $modifiedRoute->name($namePrefix . $modifiedRoute->getName());
            }

            if (empty($tree[$group->getPrefix()])) {
                $tree[] = $modifiedRoute->getName();
            } else {
                $tree[$group->getPrefix()][] = $modifiedRoute->getName();
            }

            $routeName = $modifiedRoute->getName();
            if (isset($this->routes[$routeName]) && !$modifiedRoute->isOverride()) {
                throw new InvalidArgumentException("A route with name '$routeName' already exists.");
            }
            $this->routes[$routeName] = $modifiedRoute;
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
