<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Http\Method;

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
        foreach ($items as $item) {
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

        $this->items[] = $route->getData(Route::NAME);
        $routeName = $route->getData(Route::NAME);
        if (isset($this->routes[$routeName]) && !$route->getData(Route::OVERRIDE)) {
            throw new InvalidArgumentException("A route with name '$routeName' already exists.");
        }
        $this->routes[$routeName] = $route;
    }

    /**
     * Inject a Group instance into route and item arrays.
     */
    private function injectGroup(Group $group, array &$tree, string $prefix = '', string $namePrefix = ''): void
    {
        $prefix .= $group->getData(Group::PREFIX);
        $namePrefix .= $group->getData(Group::NAME_PREFIX);
        $items = $group->getData(Group::ITEMS);
        $pattern = null;
        $host = null;
        foreach ($items as $item) {
            if ($item instanceof Group || $item->hasMiddlewares()) {
                foreach ($group->getMiddlewareDefinitions() as $middleware) {
                    $item = $item->prependMiddleware($middleware);
                }
            }

            if ($group->getData(Group::HOST) !== null && $item->getData(Route::HOST) === null) {
                $item = $item->host($group->getData(Group::HOST));
            }

            if ($item instanceof Group) {
                if ($group->hasCorsMiddleware()) {
                    $item = $item->withCors($group->getData(Group::CORS_MIDDLEWARE));
                }
                /** @var Group $item */
                if (empty($item->getData(Group::PREFIX))) {
                    $this->injectGroup($item, $tree, $prefix, $namePrefix);
                    continue;
                }
                $tree[$item->getData(Group::PREFIX)] = [];
                $this->injectGroup($item, $tree[$item->getData(Group::PREFIX)], $prefix, $namePrefix);
                continue;
            }

            /** @var Route $modifiedItem */
            $modifiedItem = $item->pattern($prefix . $item->getData(Route::PATTERN));

            if (strpos($modifiedItem->getData(Route::NAME), implode(', ', $modifiedItem->getData(Route::METHODS))) === false) {
                $modifiedItem = $modifiedItem->name($namePrefix . $modifiedItem->getData(Route::NAME));
            }

            if ($group->hasCorsMiddleware()) {
                $this->processCors($group, $host, $pattern, $modifiedItem, $tree);
            }

            if (empty($tree[$group->getData(Group::PREFIX)])) {
                $tree[] = $modifiedItem->getData(Route::NAME);
            } else {
                $tree[$group->getData(Group::PREFIX)][] = $modifiedItem->getData(Route::NAME);
            }

            $routeName = $modifiedItem->getData(Route::NAME);
            if (isset($this->routes[$routeName]) && !$modifiedItem->getData(Route::OVERRIDE)) {
                throw new InvalidArgumentException("A route with name '$routeName' already exists.");
            }
            $this->routes[$routeName] = $modifiedItem;
        }
    }

    private function processCors(Group $group, ?string &$host, ?string &$pattern, Route &$modifiedItem, array &$tree): void
    {
        $middleware = $group->getData(Group::CORS_MIDDLEWARE);
        $isNotDuplicate = !in_array(Method::OPTIONS, $modifiedItem->getData(Route::METHODS), true)
            && ($pattern !== $modifiedItem->getData(Route::PATTERN) || $host !== $modifiedItem->getData(Route::HOST));

        $pattern = $modifiedItem->getData(Route::PATTERN);
        $host = $modifiedItem->getData(Route::HOST);
        /** @var Route $optionsRoute */
        $optionsRoute = Route::options($pattern);
        if ($host !== null) {
            $optionsRoute = $optionsRoute->host($host);
        }
        if ($isNotDuplicate) {
            $optionsRoute = $optionsRoute->middleware($middleware);
            if (empty($tree[$group->getData(Group::PREFIX)])) {
                $tree[] = $optionsRoute->getData(Route::NAME);
            } else {
                $tree[$group->getData(Group::PREFIX)][] = $optionsRoute->getData(Route::NAME);
            }
            $this->routes[$optionsRoute->getData(Route::NAME)] = $optionsRoute->action(
                static fn (ResponseFactoryInterface $responseFactory) => $responseFactory->createResponse(204)
            );
        }
        $modifiedItem = $modifiedItem->prependMiddleware($middleware);
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
                $tree[$key] = $this->buildTree($item, $routeAsString);
            } else {
                $tree[] = $routeAsString ? (string)$this->getRoute($item) : $this->getRoute($item);
            }
        }
        return $tree;
    }
}
