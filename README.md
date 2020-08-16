<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii Router</h1>
    <br>
</p>

The package provides PSR-7 compatible request routing and a PSR-compatible middleware ready to be used in an application.
Instead of implementing routing from groud up, the package provides an interface for configuring routes and could be used
with one of the following adapter packages:

- [FastRoute](https://github.com/yiisoft/router-fastroute)
- ...

[![Latest Stable Version](https://poser.pugx.org/yiisoft/router/v/stable.png)](https://packagist.org/packages/yiisoft/router)
[![Total Downloads](https://poser.pugx.org/yiisoft/router/downloads.png)](https://packagist.org/packages/yiisoft/router)
[![Build status](https://github.com/yiisoft/router/workflows/build/badge.svg)](https://github.com/yiisoft/router/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/router/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/router/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/router/?branch=master)

## General usage

```php
use Yiisoft\Router\Group;
use Yiisoft\Router\RouteOld;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Fastroute\UrlMatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


$routes = [
    RouteOld::get('/', static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
        $response = $responseFactory->createResponse();
        $response->getBody()->write('You are at homepage.');
        return $response;
    }),
    RouteOld::get('/test/{id:\w+}', static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
        $id = $request->getAttribute('id');

        $response = $responseFactory->createResponse();
        $response->getBody()->write('You are at test with param ' . $id);
        return $response;
    })
];

$collector = $container->get(RouteCollectorInterface::class);
$collector->addGroup(Group::create(null, $routes));

$urlMatcher = new UrlMatcher(new RouteCollection($collector));

// $request is PSR-7 ServerRequestInterface
$result = $urlMatcher->match($request);

if (!$result->isSuccess()) {
     // 404
}

// $result->parameters() contains parameters from the match

// run middleware assigned to a route found 
$response = $result->process($request, $handler);
```

`UrlGeneratorInterface` and `UrlMatcher` are specific to adapter package used. See its readme on how to properly
configure it.

In `addMiddleware()` you can either specify PSR middleware class name or a callback.

Note that pattern specified for routes depends on the underlying routing library used.

## Route groups

Routes could be grouped. That is useful for API endpoints and similar cases:

```php
use \Yiisoft\Router\RouteOld;
use \Yiisoft\Router\Group;
use \Yiisoft\Router\RouteCollectorInterface;

// for obtaining router see adapter package of choice readme
$collector = $container->get(RouteCollectorInterface::class);
    
$collector->addGroup(Group::create('/api',[
    RouteOld::get('/comments'),
    Group::create('/posts', [
        RouteOld::get('/list'),
    ]),
]));
```

## Middleware usage

In order to simplify usage in PSR-middleware based application, there is a ready to use middleware provided:

```php
$router = $container->get(Yiisoft\Router\UrlMatcherInterface::class);
$responseFactory = $container->get(\Psr\Http\Message\ResponseFactoryInterface::class);

$routerMiddleware = new Yiisoft\Router\Middleware\Router($router, $responseFactory, $container);

// add middleware to your middleware handler of choice 
```

In case of a route match router middleware executes handler middleware attached to the route. If there is no match, next
application middleware processes the request.

## Creating URLs

URLs could be created using `UrlGeneratorInterface::generate()`. Let's assume a route is defined like the following:

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Yii\Web\SapiEmitter;
use Yiisoft\Yii\Web\ServerRequestFactory;
use Yiisoft\Yii\Web\NotFoundHandler;
use Yiisoft\Router\RouteOld;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Fastroute\UrlMatcher;


$request = $container->get(ServerRequestFactory::class)->createFromGlobals();
$responseFactory = $container->get(ResponseFactoryInterface::class);
$notFoundHandler = new NotFoundHandler($responseFactory);
$collector = $container->get(RouteCollectorInterface::class);
$collector->addRoute(RouteOld::get('/test/{id:\w+}', static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
   $id = $request->getAttribute('id');
   $response = $responseFactory->createResponse();
   $response->getBody()->write('You are at test with param ' . $id);

   return $response;
})->name('test'));
$router = new UrlMatcher(new RouteCollection($collector));
$route = $router->match($request);
$response = $route->process($request, $notFoundHandler);
$emitter = new SapiEmitter();
$emitter->emit($response, $request->getMethod() === Method::HEAD);
```

Then that is how URL could be obtained for it:

```php
use Yiisoft\Router\UrlGeneratorInterface;

function getUrl(UrlGeneratorInterface $urlGenerator, $parameters = [])
{
    return $urlGenerator->generate('test', $parameters);
}
```
