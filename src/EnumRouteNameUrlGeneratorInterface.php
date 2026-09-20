<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use BackedEnum;

/**
 * `EnumRouteNameUrlGeneratorInterface` is a {@see UrlGeneratorInterface} that additionally accepts a backed
 * enumeration as a route name, matching what {@see Route::name()} already accepts.
 *
 * Type against it instead of {@see UrlGeneratorInterface} when you pass enumeration cases to the generator:
 *
 * ```php
 * enum RouteName: string
 * {
 *     case Home = 'home';
 * }
 *
 * $url = $urlGenerator->generate(RouteName::Home);
 * ```
 *
 * @psalm-import-type UrlArgumentsType from UrlGeneratorInterface
 */
interface EnumRouteNameUrlGeneratorInterface extends UrlGeneratorInterface
{
    /**
     * Generates URL from named route, arguments, and query parameters.
     *
     * @param BackedEnum|string $name Name of the route. A backed enumeration is resolved to its backing value.
     * @param array $arguments Argument-value set. Unused arguments will be moved to query parameters, if query
     * parameter with such name doesn't exist.
     * @param array $queryParameters Parameter-value set.
     * @param string|null $hash Hash part (fragment identifier) of the URL.
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
     *
     * @return string URL generated.
     *
     * @psalm-param UrlArgumentsType $arguments
     */
    public function generate(
        string|BackedEnum $name,
        array $arguments = [],
        array $queryParameters = [],
        ?string $hash = null,
    ): string;

    /**
     * Generates absolute URL from named route, arguments, and query parameters.
     *
     * @param BackedEnum|string $name Name of the route. A backed enumeration is resolved to its backing value.
     * @param array $arguments Argument-value set. Unused arguments will be moved to query parameters, if query
     * parameter with such name doesn't exist.
     * @param array $queryParameters Parameter-value set.
     * @param string|null $hash Hash part (fragment identifier) of the URL.
     * @param string|null $scheme Host scheme.
     * @param string|null $host Host for manual setup.
     *
     * @throws RouteNotFoundException In case there is no route with the name specified.
     *
     * @return string URL generated.
     *
     * @psalm-param UrlArgumentsType $arguments
     */
    public function generateAbsolute(
        string|BackedEnum $name,
        array $arguments = [],
        array $queryParameters = [],
        ?string $hash = null,
        ?string $scheme = null,
        ?string $host = null,
    ): string;

    /**
     * Generate URL from the current route replacing some of its arguments with values specified.
     *
     * @param array $replacedArguments New argument values indexed by replaced argument names. Unused arguments will be
     * moved to query parameters, if query parameter with such name doesn't exist.
     * @param array $queryParameters Parameter-value set.
     * @param string|null $hash Hash part (fragment identifier) of the URL.
     * @param BackedEnum|string|null $fallbackRouteName Name of a route that should be used if current route
     * can not be determined. A backed enumeration is resolved to its backing value.
     *
     * @psalm-param UrlArgumentsType $replacedArguments
     */
    public function generateFromCurrent(
        array $replacedArguments,
        array $queryParameters = [],
        ?string $hash = null,
        string|BackedEnum|null $fallbackRouteName = null,
    ): string;
}
