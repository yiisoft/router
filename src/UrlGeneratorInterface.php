<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * UrlGeneratorInterface allows generating URL given route name and parameters.
 * It is preferred to type-hint against it in case you need to generate a URL.
 */
interface UrlGeneratorInterface
{
    /**
     * Generates URL from named route and parameters.
     *
     * @param string $name Name of the route.
     * @param array $parameters Parameter-value set.
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
     *
     * @return string URL generated.
     *
     * @psalm-param array<string,null|object|scalar> $parameters
     */
    public function generate(string $name, array $parameters = []): string;

    /**
     * Generates absolute URL from named route and parameters.
     *
     * @param string $name Name of the route.
     * @param array $parameters Parameter-value set.
     * @param string|null $scheme Host scheme.
     * @param string|null $host Host for manual setup.
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
     *
     * @return string URL generated.
     *
     * @psalm-param array<string,null|object|scalar> $parameters
     */
    public function generateAbsolute(
        string $name,
        array $parameters = [],
        string $scheme = null,
        string $host = null
    ): string;

    public function generateFromCurrent(array $replacedParameters, ?string $fallbackRouteName = null): string;

    public function getUriPrefix(): string;

    public function setUriPrefix(string $name): void;

    /**
     * @psalm-param null|object|scalar $value
     */
    public function setDefault(string $name, $value): void;
}
