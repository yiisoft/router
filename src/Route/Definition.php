<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

final class Definition implements DefinitionInterface
{
    private string $path;
    private array $methods;
    private ?string $name;
    private ?string $host;
    private array $schemes;
    private ?int $port;
    private array $accepts;
    private array $defaults;

    public function __construct(
        string $path,
        array $methods = [],
        ?string $name = null,
        ?string $host = null,
        array $schemes = [],
        ?int $port = null,
        array $accepts = [],
        array $defaults = []
    ) {
        $this->path = $path;
        $this->methods = $methods;
        $this->name = $name;
        $this->host = $host;
        $this->schemes = $schemes;
        $this->port = $port;
        $this->accepts = $accepts;
        $this->defaults = $defaults;
    }

    public function serialize(): string
    {
        // TODO: Implement serialize() method.
        return '';
    }

    public function unserialize($serialized): void
    {
        // TODO: Implement unserialize() method.
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function withPath(string $path): self
    {
        $definition = clone $this;
        $definition->path = $path;
        return $definition;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function withHost(?string $host): self
    {
        $definition = clone $this;
        $definition->host = $host;
        return $definition;
    }

    public function getSchemes(): array
    {
        return $this->schemes;
    }

    public function withSchemes(array $schemes): self
    {
        $definition = clone $this;
        $definition->schemes = $schemes;
        return $definition;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function withDefaults(array $defaults): self
    {
        $definition = clone $this;
        $definition->defaults = $defaults;
        return $definition;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function withMethods(array $methods): self
    {
        $definition = clone $this;
        $definition->methods = $methods;
        return $definition;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function withName(string $name): self
    {
        $definition = clone $this;
        $definition->name = $name;
        return $definition;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function withPort(?int $port): self
    {
        $definition = clone $this;
        $definition->port = $port;
        return $definition;
    }

    public function getAccepts(): ?array
    {
        return $this->accepts;
    }

    public function withAccepts(array $accepts): self
    {
        $definition = clone $this;
        $definition->accepts = $accepts;
        return $definition;
    }
}
