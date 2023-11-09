<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Http\Method;

use Yiisoft\Router\Builder\RouteBuilder;

use function array_key_exists;
use function in_array;
use function is_array;

/**
 * @psalm-type Items = array<array-key,array|string>
 */
final class RouteCollection implements RouteCollectionInterface
{
    /**
     * @psalm-var Items
     */
    private array $items = [];

    /**
     * All attached routes as Route instances.
     *
     * @var Route[]
     */
    private array $routes = [];

    public function __construct(private RouteCollectorInterface $collector)
    {
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
     * Build routes array.
     *
     * @param Group[]|Route[]|RoutableInterface[] $items
     */
    private function injectItems(array $items): void
    {
        foreach ($items as $item) {
            if ($item instanceof RoutableInterface) {
                $item = $item->toRoute();
            }
            if (!$this->isStaticRoute($item)) {
                $item->setMiddlewares(array_merge($this->collector->getMiddlewares(), $item->getMiddlewares()));
            }
            $this->injectItem($item);
        }
    }

    /**
     * Add an item into routes array.
     */
    private function injectItem(Group|Route $route): void
    {
        if ($route instanceof Group) {
            $this->injectGroup($route, $this->items);
            return;
        }

        $routeName = $route->getName();
        $this->items[] = $routeName;
        if (isset($this->routes[$routeName]) && !$route->isOverride()) {
            throw new InvalidArgumentException("A route with name '$routeName' already exists.");
        }
        $this->routes[$routeName] = $route;
    }

    /**
     * Inject a Group instance into route and item arrays.
     *
     * @psalm-param Items $tree
     */
    private function injectGroup(Group $group, array &$tree, string $prefix = '', string $namePrefix = ''): void
    {
        $prefix .= (string) $group->getPrefix();
        $namePrefix .= (string) $group->getNamePrefix();
        $items = $group->getRoutes();
        $pattern = null;
        $hosts = [];
        foreach ($items as $item) {
            if ($item instanceof RoutableInterface) {
                $item = $item->toRoute();
            }
            if (!$this->isStaticRoute($item)) {
                $item = $item->setMiddlewares(array_merge($group->getEnabledMiddlewares(), $item->getMiddlewares()));
            }

            if (!empty($group->getHosts()) && empty($item->getHosts())) {
                $item->setHosts($group->getHosts());
            }

            if ($item instanceof Group) {
                if ($group->getCorsMiddleware() !== null) {
                    $item->setCorsMiddleware($group->getCorsMiddleware());
                }
                if (empty($item->getPrefix())) {
                    $this->injectGroup($item, $tree, $prefix, $namePrefix);
                    continue;
                }
                /** @psalm-suppress PossiblyNullArrayOffset Checked group prefix on not empty above. */
                if (!isset($tree[$item->getPrefix()])) {
                    $tree[$item->getPrefix()] = [];
                }
                /**
                 * @psalm-suppress MixedArgumentTypeCoercion
                 * @psalm-suppress MixedArgument,PossiblyNullArrayOffset
                 * Checked group prefix on not empty above.
                 */
                $this->injectGroup($item, $tree[$item->getPrefix()], $prefix, $namePrefix);
                continue;
            }

            /** @var Route $item */
            $item->setPattern($prefix . $item->getPattern());

            if (!str_contains($item->getName(), implode(', ', $item->getMethods()))) {
                $item->setName($namePrefix . $item->getName());
            }

            if ($group->getCorsMiddleware() !== null) {
                $this->processCors($group, $hosts, $pattern, $item, $tree);
            }

            $routeName = $item->getName();
            $tree[] = $routeName;
            if (isset($this->routes[$routeName]) && !$item->isOverride()) {
                throw new InvalidArgumentException("A route with name '$routeName' already exists.");
            }
            $this->routes[$routeName] = $item;
        }
    }

    /**
     * @psalm-param Items $tree
     */
    private function processCors(
        Group $group,
        array &$hosts,
        ?string &$pattern,
        Route $modifiedItem,
        array &$tree
    ): void {
        /** @var array|callable|string $middleware */
        $middleware = $group->getCorsMiddleware();
        $isNotDuplicate = !in_array(Method::OPTIONS, $modifiedItem->getMethods(), true)
            && ($pattern !== $modifiedItem->getPattern() || $hosts !== $modifiedItem->getHosts());

        $pattern = $modifiedItem->getPattern();
        $hosts = $modifiedItem->getHosts();
        $optionsRoute = new Route([Method::OPTIONS], $pattern);
        if (!empty($hosts)) {
            $optionsRoute->setHosts($hosts);
        }
        if ($isNotDuplicate) {
            $optionsRoute->setMiddlewares([$middleware]);
            $optionsRoute->setAction(
                static fn (ResponseFactoryInterface $responseFactory) => $responseFactory->createResponse(204)
            );

            $routeName = $optionsRoute->getName();
            $tree[] = $routeName;
            $this->routes[$routeName] = $optionsRoute;
        }
        $modifiedItem->setMiddlewares(array_merge([$middleware], $modifiedItem->getMiddlewares()));
    }

    /**
     * Builds route tree from items.
     *
     * @psalm-param Items $items
     */
    private function buildTree(array $items, bool $routeAsString): array
    {
        $tree = [];
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                /** @psalm-var Items $item */
                $tree[$key] = $this->buildTree($item, $routeAsString);
            } else {
                $tree[] = $routeAsString ? (string) $this->getRoute($item) : $this->getRoute($item);
            }
        }
        return $tree;
    }

    private function isStaticRoute(Group|Route|RoutableInterface $item): bool
    {
        return $item instanceof Route && empty($item->getMiddlewares()) && $item->getAction() === null;
    }
}
