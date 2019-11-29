<?php
namespace Yiisoft\Router;

/**
 * UrlGeneratorInterface allows generating URL given route name and parameters.
 * It is preferred to type-hint against it in case you need to generate an URL.
 */
interface UrlGeneratorInterface
{
    public function generate(string $name, array $parameters = []): string;
    public function getUriPrefix(): string;
    public function setUriPrefix(string $name): void;
}
