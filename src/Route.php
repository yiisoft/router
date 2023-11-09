<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use InvalidArgumentException;
use Stringable;
use Yiisoft\Router\Internal\MiddlewareFilter;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
final class Route implements Stringable
{
    /**
     * @var string[]
     * @psalm-var array<array-key, string>
     */
    private array $methods = [];

    /**
     * @var string[]
     */
    private array $hosts = [];

    /**
     * @var array|callable|string|null
     */
    private $action = null;

    /**
     * @var array[]|callable[]|string[]
     * @psalm-var list<array|callable|string>
     */
    private array $middlewares = [];

    /**
     * @psalm-var list<array|callable|string>|null
     */
    private ?array $enabledMiddlewaresCache = null;

    /**
     * @var array<array-key,string>
     */
    private array $defaults = [];

    /**
     * @param array|callable|string|null $action Action handler. It is a primary middleware definition that
     * should be invoked last for a matched route.
     * @param array<array-key,scalar|Stringable|null> $defaults Parameter default values indexed by parameter names.
     * @param bool $override Marks route as override. When added it will replace existing route with the same name.
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function __construct(
        array $methods,
        private string $pattern,
        private ?string $name = null,
        array|callable|string $action = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        private bool $override = false,
        private array $disabledMiddlewares = [],
    ) {
        if (empty($methods)) {
            throw new InvalidArgumentException('$methods cannot be empty.');
        }
        $this->setMethods($methods);
        $this->action = $action;
        $this->setMiddlewares($middlewares);
        $this->setHosts($hosts);
        $this->setDefaults($defaults);
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getAction(): array|callable|string|null
    {
        return $this->action;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @return string[]
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getName(): string
    {
        return $this->name ??= (implode(', ', $this->methods) . ' ' . implode('|', $this->hosts) . $this->pattern);
    }

    public function isOverride(): bool
    {
        return $this->override;
    }

    public function getDisabledMiddlewares(): array
    {
        return $this->disabledMiddlewares;
    }

    /**
     * @return array[]|callable[]|string[]
     * @psalm-return list<array|callable|string>
     */
    public function getEnabledMiddlewares(): array
    {
        if ($this->enabledMiddlewaresCache !== null) {
            return $this->enabledMiddlewaresCache;
        }

        $this->enabledMiddlewaresCache = MiddlewareFilter::filter($this->middlewares, $this->disabledMiddlewares);
        if ($this->action !== null) {
            $this->enabledMiddlewaresCache[] = $this->action;
        }

        return $this->enabledMiddlewaresCache;
    }

    public function setMethods(array $methods): self
    {
        $this->assertListOfStrings($methods, 'methods');
        $this->methods = $methods;
        return $this;
    }

    public function setHosts(array $hosts): self
    {
        $this->assertListOfStrings($hosts, 'hosts');
        $this->hosts = [];
        foreach ($hosts as $host) {
            $host = rtrim($host, '/');

            if ($host !== '' && !in_array($host, $this->hosts, true)) {
                $this->hosts[] = $host;
            }
        }

        return $this;
    }

    public function setAction(callable|array|string|null $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function setMiddlewares(array $middlewares): self
    {
        $this->assertMiddlewares($middlewares);
        $this->middlewares = $middlewares;
        $this->enabledMiddlewaresCache = null;
        return $this;
    }

    public function setDefaults(array $defaults): self
    {
        /** @var mixed $value */
        foreach ($defaults as $key => $value) {
            if (!is_scalar($value) && !($value instanceof Stringable)) {
                throw new \InvalidArgumentException(
                    'Invalid $defaults provided, list of scalar or `Stringable` instance expected.'
                );
            }
            $this->defaults[$key] = (string) $value;
        }
        return $this;
    }

    public function setPattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setOverride(bool $override): self
    {
        $this->override = $override;
        return $this;
    }

    public function setDisabledMiddlewares(array $disabledMiddlewares): self
    {
        $this->disabledMiddlewares = $disabledMiddlewares;
        $this->enabledMiddlewaresCache = null;
        return $this;
    }

    public function __toString(): string
    {
        $result = $this->name === null
            ? ''
            : '[' . $this->name . '] ';

        if ($this->methods !== []) {
            $result .= implode(',', $this->methods) . ' ';
        }

        if (!empty($this->hosts)) {
            $quoted = array_map(static fn ($host) => preg_quote($host, '/'), $this->hosts);

            if (!preg_match('/' . implode('|', $quoted) . '/', $this->pattern)) {
                $result .= implode('|', $this->hosts);
            }
        }

        $result .= $this->pattern;

        return $result;
    }

    public function __debugInfo()
    {
        return [
            'name' => $this->name,
            'methods' => $this->methods,
            'pattern' => $this->pattern,
            'action' => $this->action,
            'hosts' => $this->hosts,
            'defaults' => $this->defaults,
            'override' => $this->override,
            'middlewares' => $this->middlewares,
            'disabledMiddlewares' => $this->disabledMiddlewares,
            'enabledMiddlewares' => $this->getEnabledMiddlewares(),
        ];
    }

    /**
     * @psalm-assert array<array-key,string> $items
     */
    private function assertListOfStrings(array $items, string $argument): void
    {
        foreach ($items as $item) {
            if (!is_string($item)) {
                throw new \InvalidArgumentException('Invalid $' . $argument . ' provided, list of string expected.');
            }
        }
    }

    /**
     * @psalm-assert list<array|callable|string> $middlewares
     */
    private function assertMiddlewares(array $middlewares): void
    {
        /** @var mixed $middleware */
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                continue;
            }

            if (is_callable($middleware) || is_array($middleware)) {
                continue;
            }

            throw new \InvalidArgumentException(
                'Invalid $middlewares provided, list of string or array or callable expected.'
            );
        }
    }
}
