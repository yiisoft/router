<?php
namespace Yiisoft\Router;

/**
 * RouterInterface combines interfaces for matching and generating URLs. Additionally it allows adding URLs.
 */
interface RouterInterface extends UrlGeneratorInterface, UrlMatcherInterface
{
    public function addRoute(Route $route): void;
    public function addGroup(Group $group): void;
}
