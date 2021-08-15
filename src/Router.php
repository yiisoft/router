<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use FastRoute\DataGenerator\GroupCountBased as RouteGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as RouteParser;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteParametersInterface;
use Yiisoft\Router\RouterInterface;
use Yiisoft\Router\UrlMatcherInterface;

use function array_merge;
use function array_reduce;
use function array_unique;

final class Router implements RouterInterface
{
    private ?RouteParametersInterface $currentRoute = null;
    /**
     * Current URI
     *
     * @var UriInterface|null
     */
    private ?UriInterface $currentUri = null;

    /**
     * Returns the current Route object
     *
     * @return RouteParametersInterface|null current route
     */
    public function getCurrentRoute(): ?RouteParametersInterface
    {
        return $this->currentRoute;
    }

    /**
     * Returns current URI
     *
     * @return UriInterface|null current URI
     */
    public function getCurrentUri(): ?UriInterface
    {
        return $this->currentUri;
    }

    public function setCurrentRoute(RouteParametersInterface $currentRoute): void
    {
        $this->currentRoute = $currentRoute;
    }

    /**
     * @param UriInterface|null $currentUri
     */
    public function setCurrentUri(?UriInterface $currentUri): void
    {
        $this->currentUri = $currentUri;
    }
}
