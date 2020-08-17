<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

interface DefinitionInterface extends \Serializable
{
    public function getPath(): string;

    public function withPath(string $path): DefinitionInterface;

    public function getHost(): ?string;

    public function withHost(?string $host): DefinitionInterface;

    public function getSchemes(): array;

    public function withSchemes(array $schemes): DefinitionInterface;

    public function getMethods(): array;

    public function withMethods(array $methods): DefinitionInterface;

    public function getDefaults(): array;

    public function withDefaults(array $defaults): DefinitionInterface;

    public function getName(): string;

    public function withName(string $name): DefinitionInterface;

    public function getPort(): ?int;

    public function withPort(?int $port): DefinitionInterface;

    public function getAccepts(): ?array;

    public function withAccepts(array $accepts): DefinitionInterface;
}
