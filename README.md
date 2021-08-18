<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://yiisoft.github.io/docs/images/yii_logo.svg" height="100px">
    </a>
    <h1 align="center">Yii Router</h1>
    <br>
</p>

The package provides PSR-7 compatible request routing and a PSR-compatible middleware ready to be used in an application.
Instead of implementing routing from ground up, the package provides an interface for configuring routes and could be used
with an adapter package. Currently, the only adapter is available, [FastRoute](https://github.com/yiisoft/router-fastroute).

[![Latest Stable Version](https://poser.pugx.org/yiisoft/router/v/stable.png)](https://packagist.org/packages/yiisoft/router)
[![Total Downloads](https://poser.pugx.org/yiisoft/router/downloads.png)](https://packagist.org/packages/yiisoft/router)
[![Build status](https://github.com/yiisoft/router/workflows/build/badge.svg)](https://github.com/yiisoft/router/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/router/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/yiisoft/router/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/router/?branch=master)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Frouter%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/yiisoft/router/master)
[![static analysis](https://github.com/yiisoft/router/workflows/static%20analysis/badge.svg)](https://github.com/yiisoft/router/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/yiisoft/router/coverage.svg)](https://shepherd.dev/github/yiisoft/router)

## General usage

```php
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Fastroute\UrlMatcher;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


$routes = [
    Route::get('/')
        ->action(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
            $response = $responseFactory->createResponse();
            $response->getBody()->write('You are at homepage.');
            return $response;
        }),
    Route::get('/test/{id:\w+}')
        ->action(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
            $id = $request->getAttribute('id');
    
            $response = $responseFactory->createResponse();
            $response->getBody()->write('You are at test with param ' . $id);
            return $response;
        })
];

$collector = $container->get(RouteCollectorInterface::class);
$collector->addGroup(Group::create(null)->routes($routes));

$urlMatcher = new UrlMatcher(new RouteCollection($collector));

// $request is PSR-7 ServerRequestInterface.
$result = $urlMatcher->match($request);

if (!$result->isSuccess()) {
     // 404
}

// $result->parameters() contains parameters from the match.

// Run middleware assigned to a route found.
$response = $result->process($request, $handler);
```

`UrlGeneratorInterface` and `UrlMatcher` are specific to adapter package used. See its readme on how to properly
configure it.

In `middleware()` and `prependMiddleware()` you can either specify PSR middleware class name or a callback.

Note that pattern specified for routes depends on the underlying routing library used.

## Route groups

Routes could be grouped. That is useful for API endpoints and similar cases:

```php
use \Yiisoft\Router\Route;
use \Yiisoft\Router\Group;
use \Yiisoft\Router\RouteCollectorInterface;

// for obtaining router see adapter package of choice readme
$collector = $container->get(RouteCollectorInterface::class);
    
$collector->addGroup(Group::create('/api')->routes([
    Route::get('/comments'),
    Group::create('/posts')->routes([
        Route::get('/list'),
    ]),
]));
```

## Middleware usage

In order to simplify usage in PSR-middleware based application, there is a ready to use middleware provided:

```php
$router = $container->get(Yiisoft\Router\UrlMatcherInterface::class);
$responseFactory = $container->get(\Psr\Http\Message\ResponseFactoryInterface::class);

$routerMiddleware = new Yiisoft\Router\Middleware\Router($router, $responseFactory, $container);

// Add middleware to your middleware handler of choice.
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
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Fastroute\UrlMatcher;


$request = $container->get(ServerRequestFactory::class)->createFromGlobals();
$responseFactory = $container->get(ResponseFactoryInterface::class);
$notFoundHandler = new NotFoundHandler($responseFactory);
$collector = $container->get(RouteCollectorInterface::class);
$collector->addRoute(Route::get('/test/{id:\w+}')->action(static function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
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

## Obtain current route and URI

Current route (matched last) and URI could be obtained the following:

```php
use Yiisoft\Router\CurrentRoute;

function getCurrentRoute(CurrentRoute $currentRoute)
{
    return $currentRoute->getRoute();
}

function getCurrentUri(CurrentRoute $currentRoute)
{
    return $currentRoute->getUri();
}

```

### Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```shell
./vendor/bin/phpunit
```

### Mutation testing

The package tests are checked with [Infection](https://infection.github.io/) mutation framework. To run it:

```shell
./vendor/bin/infection
```

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

### Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

### Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii Router is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
