<?php
namespace Yiisoft\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route defines a mapping from URL to callback / name and vice versa
 */
class Route implements MiddlewareInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $methods;

    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $host;

    /**
     * @var MiddlewareInterface
     */
    private $middleware;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $defaults = [];

    private function __construct()
    {
    }

    public static function get(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::GET];
        $new->pattern = $pattern;
        return $new;
    }

    public static function post(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::POST];
        $new->pattern = $pattern;
        return $new;
    }

    public static function put(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::PUT];
        $new->pattern = $pattern;
        return $new;
    }

    public static function delete(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::DELETE];
        $new->pattern = $pattern;
        return $new;
    }

    public static function patch(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::PATCH];
        $new->pattern = $pattern;
        return $new;
    }

    public static function head(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::HEAD];
        $new->pattern = $pattern;
        return $new;
    }

    public static function options(string $pattern): self
    {
        $new = new static();
        $new->methods = [Method::OPTIONS];
        $new->pattern = $pattern;
        return $new;
    }

    public static function methods(array $methods, string $pattern): self
    {
        $new = new static();
        $new->methods = $methods;
        $new->pattern = $pattern;
        return $new;
    }

    public static function anyMethod(string $pattern): self
    {
        $new = new static();
        $new->methods = Method::ANY;
        $new->pattern = $pattern;
        return $new;
    }

    public function name(string $name): self
    {
        $new = clone $this;
        $new->name = $name;
        return $new;
    }

    public function host(string $host): self
    {
        $new = clone $this;
        $new->host = rtrim($host, '/');
        return $new;
    }

    /**
     * Parameter validation rules indexed by parameter names
     *
     * @param array $parameters
     * @return Route
     */
    public function parameters(array $parameters): self
    {
        $new = clone $this;
        $new->parameters = $parameters;
        return $new;
    }

    /**
     * Parameter default values indexed by parameter names
     *
     * @param array $defaults
     * @return Route
     */
    public function defaults(array $defaults): self
    {
        $new = clone $this;
        $new->defaults = $defaults;
        return $new;
    }

    /**
     * Speicifes a handler that should be invoked for a matching route.
     * It can be either a PSR middleware or a callable with the following signature:
     *
     * ```
     * function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
     * ```
     *
     * @param MiddlewareInterface|callable $middleware
     * @return Route
     */
    public function to($middleware): self
    {
        $new = clone $this;
        if (is_callable($middleware)) {
            $middleware = $this->wrapCallable($middleware);
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException('Parameter should be either a PSR middleware or a callable.');
        }

        $new->middleware = $middleware;
        return $new;
    }

    private function wrapCallable(callable $callback): MiddlewareInterface
    {
        return new class($callback) implements MiddlewareInterface {
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return \call_user_func($this->callback, $request, $handler);
            }
        };
    }

    public function __toString()
    {
        $result = '';

        if ($this->name !== null) {
            $result .= '[' . $this->name . '] ';
        }

        if ($this->methods !== null) {
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
        return $this->name ?? implode(', ', $this->methods) . ' ' . $this->pattern;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middleware->process($request, $handler);
    }

    public static function __set_state(array $properties): self
    {
        $new = new self();
        $new->name = $properties['name'];
        $new->methods = $properties['methods'];
        $new->pattern = $properties['pattern'];
        $new->host = $properties['host'];
        $new->middleware = $properties['middleware'];
        $new->parameters = $properties['parameters'];
        $new->defaults = $properties['defaults'];
        return $new;
    }
}
