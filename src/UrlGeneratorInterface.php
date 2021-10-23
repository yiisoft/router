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
     *
     * @throws RouteNotFoundException in case there is no route with the name specified
     *
     * @return string URL generated
     */
    public function generate(string $name, array $parameters = []): string;

    /**
     * Generates absolute URL from named route and parameters
     *
     * @param string $name name of the route
     * @param array $parameters parameter-value set
     * @param string|null $scheme host scheme
     * @param string|null $host host for manual setup
     *
     * @throws RouteNotFoundException in case there is no route with the name specified
     *
     * @return string URL generated
     */
    public function generateAbsolute(string $name, array $parameters = [], string $scheme = null, string $host = null): string;

    public function getUriPrefix(): string;

    public function setUriPrefix(string $name): void;

    public function getLocales(): array;

    public function setLocales(array $locales): void;

    public function setLocaleParameterName(string $localeParameterName): void;
}
