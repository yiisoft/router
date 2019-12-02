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
}
