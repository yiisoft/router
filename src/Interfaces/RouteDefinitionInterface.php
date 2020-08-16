<?php

declare(strict_types=1);

namespace Yiisoft\Router\Interfaces;

interface RouteDefinitionInterface extends \Serializable
{
    public function getPath(): string;

    public function withPath(string $path): RouteDefinitionInterface;

    public function getHost(): ?string;

    public function withHost(?string $host): RouteDefinitionInterface;

    public function getSchemes(): array;

    public function withSchemes(array $schemes): RouteDefinitionInterface;

    public function getMethods(): array;

    public function withMethods(array $methods): RouteDefinitionInterface;

    public function getDefaults(): array;

    public function withDefaults(array $defaults): RouteDefinitionInterface;

    public function getName(): string;

    public function withName(string $name): RouteDefinitionInterface;

    public function getPort(): ?int;

    public function withPort(?int $port): RouteDefinitionInterface;

    public function getAccepts(): ?array;

    public function withAccepts(array $accepts): RouteDefinitionInterface;
}
