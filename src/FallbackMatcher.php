<?php

declare(strict_types=1);


namespace Yiisoft\Router;


use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class FallbackMatcher implements UrlMatcherInterface
{
    private UrlMatcherInterface $urlMatcher;
    private ?Route $currentRoute = null;
    private ?UriInterface $currentUri = null;
    private bool $isFallback = false;

    public function __construct(UrlMatcherInterface $urlMatcher)
    {
        $this->urlMatcher = $urlMatcher;
    }

    public function match(ServerRequestInterface $request): MatchingResult
    {
        $result = $this->urlMatcher->match($request);
        if (!$result->isSuccess()) {
            $this->isFallback = true;
            // fallback match
            // $this->currentRoute = ...
            // $this->currentUrl = ...
        }
        return $result;
    }

    public function getCurrentRoute(): ?Route
    {
        return $this->isFallback ? $this->currentRoute : $this->urlMatcher->getCurrentRoute();
    }

    public function getCurrentUri(): ?UriInterface
    {
        return $this->isFallback ? $this->currentUri : $this->urlMatcher->getCurrentUri();
    }
}
