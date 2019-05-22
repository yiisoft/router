<?php
namespace Yiisoft\Router;

/**
 * RouterInterface combiles interfaces for matching and generating URLs. Additionally it allows adding URLs.
 */
interface RouterInterface extends UrlGeneratorInterface, UrlMatcherInterface
{
    public function addRoute(Route $route): void;
}
