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
     * @psalm-param array<string,null|Stringable|scalar> $parameters
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
     */
    public function generate(string $name, array $arguments = [], array $queryParameters = []): string;

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
     * @psalm-param array<string,null|Stringable|scalar> $parameters
     */
    public function generateAbsolute(
        string $name,
        array $arguments = [],
        array $queryParameters = [],
        string $scheme = null,
        string $host = null
    ): string;

    /**
     * Generate URL from the current route replacing some of its arguments with values specified.
     *
     * @param array $replacedArguments New argument values indexed by replaced argument names.
     * @param string|null $fallbackRouteName Name of a route that should be used if current route
     * can not be determined.
     *
     * @psalm-param array<string,null|Stringable|scalar> $replacedArguments
     */
    public function generateFromCurrent(array $replacedArguments, ?string $fallbackRouteName = null): string;

    public function getUriPrefix(): string;

    public function setUriPrefix(string $name): void;

    /**
     * Set default argument value.
     *
     * @param string $name Name of argument to provide default value for.
     * @param mixed $value Default value.
     *
     * @psalm-param null|Stringable|scalar $value
     */
    public function setDefaultArgument(string $name, $value): void;
}
