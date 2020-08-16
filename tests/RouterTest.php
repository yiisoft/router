<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\DefaultDispatcher;
use Yiisoft\Router\Interfaces\MatcherInterface;
use Yiisoft\Router\Interfaces\RouteCollectionInterface;
use Yiisoft\Router\Interfaces\RouteInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\Router;

final class RouterTest extends TestCase
{
    public function testRouter(): void
    {
        $request = new ServerRequest('GET', '/');
        $router = $this->createRouter()
            ->addRoute(Route::get('/', $this->createMiddleware()));
        $response = $router->handle($request);
        $this->assertSame(200, $response->getStatusCode());
    }

    private function createRouter(): Router
    {
        return new Router(new RouteCollection(), $this->getMatcher(), new DefaultDispatcher(null));
    }

    private function createMiddleware(): MiddlewareInterface
    {
        return new class() implements MiddlewareInterface {

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return new Response();
            }
        };
    }

    private function getMatcher(): MatcherInterface
    {
        return new class() implements MatcherInterface {

            public function match(ServerRequestInterface $request): MatchingResult
            {
                // TODO: Implement match() method.
            }

            public function matchForCollection(
                RouteCollectionInterface $collection,
                ServerRequestInterface $request
            ): MatchingResult {
                $routes = $collection->getRoutes();

                foreach ($routes as $route) {
                    /** @var RouteInterface $route */
                    if ($request->getUri()->getPath() === $route->getDefinition()->getPath()) {
                        return MatchingResult::fromSuccess($route, []);
                    }
                }

                return MatchingResult::fromFailure();
            }
        };
    }
}
