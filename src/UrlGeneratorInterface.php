<?php

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
     * @param string|null $host host for manual setup
     * @return string URL generated
     * @throws RouteNotFoundException in case there is no route with the name specified
     */
    public function generateAbsolute(string $name, array $parameters = [], string $host = null): string;

    /**
     * Normalize URL by ensuring that it use specified scheme.
     *
     * If URL is relative or scheme is null, normalization is skipped.
     *
     * @param string $url the URL to process
     * @param string $scheme the URI scheme used in URL (e.g. `http` or `https`). Use empty string to
     * create protocol-relative URL (e.g. `//example.com/path`)
     * @return string the processed URL
     * @since 2.0.11
     */
    public function ensureScheme(string $url, ?string $scheme): string;

    /**
     * Returns a value indicating whether a URL is relative.
     * A relative URL does not have host info part.
     * @param string $url the URL to be checked
     * @return bool whether the URL is relative
     */
    public function isRelative(string $url): bool;

    public function getUriPrefix(): string;

    public function setUriPrefix(string $name): void;
}
