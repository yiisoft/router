<?php

namespace Yiisoft\Router;

/**
 * RouterInterface combines interfaces for adding routes, matching URLs and generating URLs.
 */
interface RouterInterface extends UrlGeneratorInterface, UrlMatcherInterface, RouteCollectorInterface
{
}
