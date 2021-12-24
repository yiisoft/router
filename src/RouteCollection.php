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

        $routeName = $route->getData('name');
        $this->items[] = $routeName;
        if (isset($this->routes[$routeName]) && !$route->getData('override')) {
            throw new InvalidArgumentException("A route with name '$routeName' already exists.");
        }
        $this->routes[$routeName] = $route;
    }

    /**
     * Inject a Group instance into route and item arrays.
     */
    private function injectGroup(Group $group, array &$tree, string $prefix = '', string $namePrefix = ''): void
    {
        $prefix .= $group->getData('prefix');
        $namePrefix .= $group->getData('namePrefix');
        $items = $group->getData('items');
        $pattern = null;
        $host = null;
        foreach ($items as $item) {
            if ($item instanceof Group || $item->getData('hasMiddlewares')) {
                foreach ($group->getData('middlewareDefinitions') as $middleware) {
                    $item = $item->prependMiddleware($middleware);
                }
            }

            if ($group->getData('host') !== null && $item->getData('host') === null) {
                /** @psalm-suppress PossiblyNullArgument Checked group host on not null above */
                $item = $item->host($group->getData('host'));
            }

            if ($item instanceof Group) {
                if ($group->getData('hasCorsMiddleware')) {
                    $item = $item->withCors($group->getData('corsMiddleware'));
                }
                if (empty($item->getData('prefix'))) {
                    $this->injectGroup($item, $tree, $prefix, $namePrefix);
                    continue;
                }
                /** @psalm-suppress PossiblyNullArrayOffset Checked group prefix on not empty above */
                $tree[$item->getData('prefix')] = [];
                /** @psalm-suppress PossiblyNullArrayOffset Checked group prefix on not empty above */
                $this->injectGroup($item, $tree[$item->getData('prefix')], $prefix, $namePrefix);
                continue;
            }

            $modifiedItem = $item->pattern($prefix . $item->getData('pattern'));

            if (strpos($modifiedItem->getData('name'), implode(', ', $modifiedItem->getData('methods'))) === false) {
                $modifiedItem = $modifiedItem->name($namePrefix . $modifiedItem->getData('name'));
            }

            if ($group->getData('hasCorsMiddleware')) {
                $this->processCors($group, $host, $pattern, $modifiedItem, $tree);
            }

            if (empty($tree[$group->getData('prefix')])) {
                $tree[] = $modifiedItem->getData('name');
            } else {
                /** @psalm-suppress PossiblyNullArrayOffset Checked group prefix on not empty above */
                $tree[$group->getData('prefix')][] = $modifiedItem->getData('name');
            }

            $routeName = $modifiedItem->getData('name');
            if (isset($this->routes[$routeName]) && !$modifiedItem->getData('override')) {
                throw new InvalidArgumentException("A route with name '$routeName' already exists.");
            }
            $this->routes[$routeName] = $modifiedItem;
        }
    }

    private function processCors(
        Group $group,
        ?string &$host,
        ?string &$pattern,
        Route &$modifiedItem,
        array &$tree
    ): void {
        $middleware = $group->getData('corsMiddleware');
        $isNotDuplicate = !in_array(Method::OPTIONS, $modifiedItem->getData('methods'), true)
            && ($pattern !== $modifiedItem->getData('pattern') || $host !== $modifiedItem->getData('host'));

        $pattern = $modifiedItem->getData('pattern');
        $host = $modifiedItem->getData('host');
        $optionsRoute = Route::options($pattern);
        if ($host !== null) {
            $optionsRoute = $optionsRoute->host($host);
        }
        if ($isNotDuplicate) {
            $optionsRoute = $optionsRoute->middleware($middleware);
            if (empty($tree[$group->getData('prefix')])) {
                $tree[] = $optionsRoute->getData('name');
            } else {
                /** @psalm-suppress PossiblyNullArrayOffset Checked group prefix on not empty above */
                $tree[$group->getData('prefix')][] = $optionsRoute->getData('name');
            }
            $this->routes[$optionsRoute->getData('name')] = $optionsRoute->action(
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
