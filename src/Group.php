<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Attribute;
use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Router\Internal\MiddlewareFilter;

use function in_array;

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
    private bool $routesAdded = false;
    private bool $middlewareAdded = false;

    /**
     * @psalm-var list<array|callable|string>|null
     */
    private ?array $enabledMiddlewaresCache = null;

    /**
     * @var array|callable|string|null Middleware definition for CORS requests.
     */
    private $corsMiddleware = null;

    /**
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function __construct(
        private ?string $prefix = null,
        array $middlewares = [],
        array $hosts = [],
        private ?string $namePrefix = null,
        private array $disabledMiddlewares = [],
        array|callable|string|null $corsMiddleware = null
    ) {
        $this->assertMiddlewares($middlewares);
        $this->assertHosts($hosts);
        $this->middlewares = $middlewares;
        $this->hosts = $hosts;
        $this->corsMiddleware = $corsMiddleware;
    }

    /**
     * Create a new group instance.
     *
     * @param string|null $prefix URL prefix to prepend to all routes of the group.
     */
    public static function create(?string $prefix = null): self
    {
        return new self($prefix);
    }

    public function routes(self|Route ...$routes): self
    {
        if ($this->middlewareAdded) {
            throw new RuntimeException('routes() can not be used after prependMiddleware().');
        }

        $new = clone $this;
        $new->routes = $routes;
        $new->routesAdded = true;

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
        if ($this->routesAdded) {
            throw new RuntimeException('middleware() can not be used after routes().');
        }

        $new = clone $this;
        array_push(
            $new->middlewares,
            ...array_values($definition)
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
            ...array_values($definition)
        );

        $new->middlewareAdded = true;
        $new->enabledMiddlewaresCache = null;

        return $new;
    }

    public function namePrefix(string $namePrefix): self
    {
        $new = clone $this;
        $new->namePrefix = $namePrefix;
        return $new;
    }

    public function host(string $host): self
    {
        return $this->hosts($host);
    }

    public function hosts(string ...$hosts): self
    {
        $new = clone $this;

        foreach ($hosts as $host) {
            $host = rtrim($host, '/');

            if ($host !== '' && !in_array($host, $new->hosts, true)) {
                $new->hosts[] = $host;
            }
        }

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
     * @psalm-template T as string
     *
     * @psalm-param T $key
     *
     * @psalm-return (
     *   T is ('prefix'|'namePrefix'|'host') ? string|null :
     *   (T is 'routes' ? Group[]|Route[] :
     *     (T is 'hosts' ? array<array-key, string> :
     *       (T is ('hasCorsMiddleware') ? bool :
     *         (T is 'enabledMiddlewares' ? list<array|callable|string> :
     *           (T is 'corsMiddleware' ? array|callable|string|null : mixed)
     *         )
     *       )
     *     )
     *   )
     * )
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

    private function assertHosts(array $hosts): void
    {
        foreach ($hosts as $host) {
            if (!is_string($host)) {
                throw new \InvalidArgumentException('Invalid $hosts provided, list of string expected.');
            }
        }
    }

    /**
     * @psalm-assert array<array|callable|string> $middlewareDefinitions
     */
    private function assertMiddlewares(array $middlewareDefinitions): void
    {
        /** @var mixed $middlewareDefinition */
        foreach ($middlewareDefinitions as $middlewareDefinition) {
            if (is_string($middlewareDefinition) || is_callable($middlewareDefinition) || is_array($middlewareDefinition)) {
                continue;
            }

            throw new \InvalidArgumentException(
                'Invalid $middlewareDefinitions provided, list of string or array or callable expected.'
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
            return $this->enabledMiddlewaresCache;
        }

        $this->enabledMiddlewaresCache = MiddlewareFilter::filter($this->middlewares, $this->disabledMiddlewares);

        return $this->enabledMiddlewaresCache;
    }
}
