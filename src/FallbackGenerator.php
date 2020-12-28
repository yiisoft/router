<?php

declare(strict_types=1);

namespace Yiisoft\Router;

class FallbackGenerator implements UrlGeneratorInterface
{
    private UrlGeneratorInterface $urlGenerator;

    /**
     * FallbackGenerator constructor.
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function generate(string $name, array $parameters = []): string
    {
        try {
            return $this->urlGenerator->generate($name, $parameters);
        } catch (RouteNotFoundException $e) {
            // generate fallback URL
        }
    }

    public function generateAbsolute(string $name, array $parameters = [], string $scheme = null, string $host = null): string
    {
        try {
            return $this->urlGenerator->generateAbsolute($name, $parameters, $scheme, $host);
        } catch (RouteNotFoundException $e) {
            // generate fallback URL
        }
    }

    public function getUriPrefix(): string
    {
        return $this->urlGenerator->getUriPrefix();
    }

    public function setUriPrefix(string $name): void
    {
        $this->urlGenerator->setUriPrefix($name);
    }
}
