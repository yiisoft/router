<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Attribute;
use InvalidArgumentException;
use Yiisoft\Router\Internal\MiddlewareFilter;

use function in_array;
use function is_array;
use function is_callable;
use function is_string;

/**
 * Route group that allows organizing routes with common properties.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Group
{
    /**
     * @var Group[]|Route[]
     */
    private array $routes = [];

    /**
     * @var array[]|callable[]|string[]
     * @psalm-var list<array|callable|string>
     */
    private array $middlewares = [];

    /**
     * @var string[]
     */
    private array $hosts = [];

    /**
     * @psalm-var list<array|callable|string>|null
     */
    private ?array $enabledMiddlewaresCache = null;

    /**
     * @var array|callable|string|null Middleware definition for CORS requests.
     */
    private $corsMiddleware = null;

    /**
     * @param string|null $prefix URL prefix to prepend to all routes of the group.
     * @param array[]|callable[]|string[] $middlewares Middleware definitions.
     * @param string[] $hosts List of host names.
     * @param string|null $namePrefix Prefix for route names.
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * @param array|callable|string|null $corsMiddleware Middleware definition for CORS requests.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     *
     * @psalm-param list<array|callable|string> $middlewares
     */
    public function __construct(
        private readonly ?string $prefix = null,
        array $middlewares = [],
        array $hosts = [],
        private ?string $namePrefix = null,
        private array $disabledMiddlewares = [],
        array|callable|string|null $corsMiddleware = null,
    ) {
        $this->assertMiddlewaresValid($middlewares);
        $this->assertHostsValid($hosts);
        $this->middlewares = $middlewares;
        $this->hosts = $this->normalizeHosts($hosts);
        $this->corsMiddleware = $corsMiddleware;
    }

    /**
     * Create a new group instance.
     *
     * @param string|null $prefix URL prefix to prepend to all routes of the group.
     *
     * @deprecated Use `new Group()` instead.
     */
    public static function create(?string $prefix = null): self
    {
        return new self($prefix);
    }

    /**
     * Sets the routes for this group.
     *
     * @param self|Route ...$routes Routes or sub-groups to include in this group.
     * @return self New instance with the specified routes.
     */
    public function routes(self|Route ...$routes): self
    {
        $new = clone $this;
        $new->routes = $routes;

        return $new;
    }

    /**
     * Adds a middleware definition that handles CORS requests.
     * If set, routes for {@see Method::OPTIONS} request will be added automatically.
     *
     * @param array|callable|string|null $middlewareDefinition Middleware definition for CORS requests.
     */
    public function withCors(array|callable|string|null $middlewareDefinition): self
    {
        $group = clone $this;
        $group->corsMiddleware = $middlewareDefinition;

        return $group;
    }

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     */
    public function middleware(array|callable|string ...$definition): self
    {
        $new = clone $this;
        array_push(
            $new->middlewares,
            ...array_values($definition),
        );

        $new->enabledMiddlewaresCache = null;

        return $new;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     */
    public function prependMiddleware(array|callable|string ...$definition): self
    {
        $new = clone $this;
        array_unshift(
            $new->middlewares,
            ...array_values($definition),
        );

        $new->enabledMiddlewaresCache = null;

        return $new;
    }

    /**
     * Sets the name prefix for all routes in this group.
     *
     * @param string $namePrefix Prefix to prepend to route names.
     * @return self New instance with the specified name prefix.
     */
    public function namePrefix(string $namePrefix): self
    {
        $new = clone $this;
        $new->namePrefix = $namePrefix;
        return $new;
    }

    /**
     * Adds a host requirement for all routes in this group.
     *
     * @param string $host Host name to match.
     * @return self New instance with the specified host.
     */
    public function host(string $host): self
    {
        return $this->hosts($host);
    }

    /**
     * Sets host requirements for all routes in this group.
     *
     * @param string ...$hosts Host names to match.
     * @return self New instance with the specified hosts.
     */
    public function hosts(string ...$hosts): self
    {
        $new = clone $this;
        $new->hosts = $this->normalizeHosts($hosts);

        return $new;
    }

    /**
     * Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function disableMiddleware(mixed ...$definition): self
    {
        $new = clone $this;
        array_push(
            $new->disabledMiddlewares,
            ...array_values($definition),
        );

        $new->enabledMiddlewaresCache = null;

        return $new;
    }

    /**
     * Returns group data by key.
     *
     * @param string $key Data key to retrieve (`prefix`, `namePrefix`, `host`, `hosts`, `corsMiddleware`, `routes`,
     * `hasCorsMiddleware`, `enabledMiddlewares`).
     * @return mixed The requested data.
     * @throws InvalidArgumentException If the key is unknown.
     */
    public function getData(string $key): mixed
    {
        return match ($key) {
            'prefix' => $this->prefix,
            'namePrefix' => $this->namePrefix,
            'host' => $this->hosts[0] ?? null,
            'hosts' => $this->hosts,
            'corsMiddleware' => $this->corsMiddleware,
            'routes' => $this->routes,
            'hasCorsMiddleware' => $this->corsMiddleware !== null,
            'enabledMiddlewares' => $this->getEnabledMiddlewares(),
            default => throw new InvalidArgumentException('Unknown data key: ' . $key),
        };
    }

    private function assertHostsValid(array $hosts): void
    {
        foreach ($hosts as $host) {
            if (!is_string($host)) {
                throw new InvalidArgumentException('Invalid $hosts provided, list of string expected.');
            }
        }
    }

    /**
     * @psalm-assert array<array|callable|string> $middlewareDefinitions
     */
    private function assertMiddlewaresValid(array $middlewareDefinitions): void
    {
        /** @var mixed $middlewareDefinition */
        foreach ($middlewareDefinitions as $middlewareDefinition) {
            if (is_string($middlewareDefinition) || is_callable($middlewareDefinition) || is_array($middlewareDefinition)) {
                continue;
            }

            throw new InvalidArgumentException(
                'Invalid $middlewareDefinitions provided, list of string or array or callable expected.',
            );
        }
    }

    /**
     * @return array[]|callable[]|string[]
     * @psalm-return list<array|callable|string>
     */
    private function getEnabledMiddlewares(): array
    {
        if ($this->enabledMiddlewaresCache !== null) {
            /** @infection-ignore-all */
            return $this->enabledMiddlewaresCache;
        }

        $this->enabledMiddlewaresCache = MiddlewareFilter::filter($this->middlewares, $this->disabledMiddlewares);

        return $this->enabledMiddlewaresCache;
    }

    /**
     * @param string[] $hosts
     *
     * @return array<array-key, string>
     */
    private function normalizeHosts(array $hosts): array
    {
        $normalizedHosts = [];
        foreach ($hosts as $host) {
            $host = rtrim($host, '/');

            if ($host !== '' && !in_array($host, $normalizedHosts, true)) {
                $normalizedHosts[] = $host;
            }
        }

        return $normalizedHosts;
    }
}
