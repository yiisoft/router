<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * UrlGeneratorInterface allows generating URL given route name and parameters.
 * It is preferred to type-hint against it in case you need to generate an URL.
 */
interface UrlGeneratorInterface
{
    /**
     * Generates URL from named route and parameters
     *
     * @param string $name name of the route
     * @param array $parameters parameter-value set
     * @return string URL generated
     * @throws RouteNotFoundException in case there is no route with the name specified
     */
    public function generate(string $name, array $parameters = []): string;

    /**
     * Generates absolute URL from named route and parameters
     *
     * @param string $name name of the route
     * @param array $parameters parameter-value set
     * @param string|null $scheme host scheme
     * @param string|null $host host for manual setup
     * @return string URL generated
     * @throws RouteNotFoundException in case there is no route with the name specified
     */
    public function generateAbsolute(string $name, array $parameters = [], string $scheme = null, string $host = null): string;

    public function getUriPrefix(): string;

    public function setUriPrefix(string $name): void;
}
