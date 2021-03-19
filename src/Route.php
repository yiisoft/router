<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Http\Method;

/**
 * Route defines a mapping from URL to callback / name and vice versa.
 */
final class Route
{
    private ?string $name = null;
    /** @var string[] */
    private array $methods;
    private string $pattern;
    /** @var array|callable|string */
    private $handler;

    private ?string $host = null;
    private bool $override = false;

    private array $middlewareDefinitions = [];
    private array $disabledMiddlewareDefinitions = [];
    private array $defaults = [];

    /**
     * @param string[] $methods
     * @param string $pattern
     * @param array|callable|string|null $handler
     */
    private function __construct(array $methods, string $pattern, $handler)
    {
        $this->methods = $methods;
        $this->pattern = $pattern;
        $this->handler = $handler;
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function get(string $pattern, $handler): self
    {
        return new self([Method::GET], $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function post(string $pattern, $handler): self
    {
        return new self([Method::POST], $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function put(string $pattern, $handler): self
    {
        return new self([Method::PUT], $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function delete(string $pattern, $handler): self
    {
        return new self([Method::DELETE], $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function patch(string $pattern, $handler): self
    {
        return new self([Method::PATCH], $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function head(string $pattern, $handler): self
    {
        return new self([Method::HEAD], $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function options(string $pattern, $handler): self
    {
        return new self([Method::OPTIONS], $pattern, $handler);
    }

    /**
     * @param array $methods
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function methods(array $methods, string $pattern, $handler): self
    {
        return new self($methods, $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param array|callable|string $handler
     *
     * @return self
     */
    public static function anyMethod(string $pattern, $handler = null): self
    {
        return new self(Method::ALL, $pattern, $handler);
    }

    public function name(string $name): self
    {
        $route = clone $this;
        $route->name = $name;
        return $route;
    }

    public function pattern(string $pattern): self
    {
        $new = clone $this;
        $new->pattern = $pattern;
        return $new;
    }

    public function host(string $host): self
    {
        $route = clone $this;
        $route->host = rtrim($host, '/');
        return $route;
    }

    /**
     * Marks route as override. When added it will replace existing route with the same name.
     *
     * @return self
     */
    public function override(): self
    {
        $route = clone $this;
        $route->override = true;
        return $route;
    }

    /**
     * Parameter default values indexed by parameter names.
     *
     * @param array $defaults
     *
     * @return self
     */
    public function defaults(array $defaults): self
    {
        $route = clone $this;
        $route->defaults = $defaults;
        return $route;
    }

    /**
     * Adds a handler middleware definition to the pipeline that should be invoked for a matched route.
     * First added handler will be executed first.
     *
     * @param $middlewareDefinition mixed
     *
     * @return self
     */
    public function addMiddleware($middlewareDefinition): self
    {
        $route = clone $this;
        $route->middlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    public function disableMiddleware($middlewareDefinition): self
    {
        $route = clone $this;
        $route->disabledMiddlewareDefinitions[] = $middlewareDefinition;
        return $route;
    }

    public function getMiddlewareDefinitions(): array
    {
        foreach ($this->middlewareDefinitions as $index => $definition) {
            if (in_array($definition, $this->disabledMiddlewareDefinitions)) {
                unset($this->middlewareDefinitions[$index]);
            }
        }

        return $this->middlewareDefinitions;
    }

    public function __toString(): string
    {
        $result = '';

        if ($this->name !== null) {
            $result .= '[' . $this->name . '] ';
        }

        if ($this->methods !== []) {
            $result .= implode(',', $this->methods) . ' ';
        }
        if ($this->host !== null && strrpos($this->pattern, $this->host) === false) {
            $result .= $this->host;
        }
        $result .= $this->pattern;

        return $result;
    }

    public function getName(): string
    {
        return $this->name ?? (implode(', ', $this->methods) . ' ' . $this->host . $this->pattern);
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array|callable|string
     */
    public function getHandler()
    {
        return $this->handler;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function isOverride(): bool
    {
        return $this->override;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }
}
