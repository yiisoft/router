<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Stringable;

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
     * @return string URL generated.
     *
     * @psalm-param array<null|Stringable|scalar> $parameters
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
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
     * @psalm-param array<null|Stringable|scalar> $parameters
     */
    public function generateAbsolute(
        string $name,
        array $parameters = [],
        string $scheme = null,
        string $host = null
    ): string;

    /**
     * Generate URL from the current route replacing some of its parameters with values specified.
     *
     * @param array $replacedParameters New parameter values indexed by replaced parameter names.
     * @param string|null $fallbackRouteName Name of a route that should be used if current route
     * can not be determined.
     *
     * @psalm-param array<null|Stringable|scalar> $replacedParameters
     */
    public function generateFromCurrent(array $replacedParameters, ?string $fallbackRouteName = null): string;

    public function getUriPrefix(): string;

    public function setUriPrefix(string $name): void;

    /**
     * Set default parameter value.
     *
     * @param string $name Name of parameter to provide default value for.
     * @param mixed $value Default value.
     *
     * @psalm-param null|Stringable|scalar $value
     */
    public function setDefault(string $name, $value): void;
}
