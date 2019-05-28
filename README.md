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
[![Build Status](https://travis-ci.com/yiisoft/router.svg?branch=master)](https://travis-ci.com/yiisoft/router)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiisoft/router/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiisoft/router/?branch=master)

## General usage

```php

// for obtaining router see adapter package of choice readme
$router = ...

$router->addRoute(Route::get('/')->to(function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
    $response = $responseFactory->createResponse();
    $response->getBody()->write('You are at homepage.');
    return $response;
}));

$router->addRoute(Route::get('/test/{id:\w+}')->to(function (ServerRequestInterface $request, RequestHandlerInterface $next) use ($responseFactory) {
    $id = $request->getAttribute('id');

    $response = $responseFactory->createResponse();
    $response->getBody()->write('You are at test with param ' . $id);
    return $response;
}));

// $request is PSR-7 ServerRequestInterface
$result = $this->matcher->match($request);

if (!$result->isSuccess()) {
     // 404
}

// $result->parameters() contains parameters from the match

// run middleware assigned to a route found 
$response = $result->process($request, $handler);
```

In `to` you can either specify PSR middleware or a callback.

Note that pattern specified for routes depends on the underlying routing library used.

## Route groups

Routes could be grouped. That is useful for API endpoints and similar cases:

```php
// for obtaining router see adapter package of choice readme
$router = ...

$apiRoutes = (new Group('/api'))
    ->addRoute(Route::get('/posts'))
    ->addRoute(Route::get('/comments'));
    
$router->addGroup($apiRoutes);
```

## Middleware usage

In order to simplify usage in PSR-middleware based application, there is a ready to use middleware provided:

```php
$router = $container->get(Yiisoft\Router\UrlMatcherInterface::class);

$routerMiddleware = new Yiisoft\Router\Middleware\Router();

// add middleware to your middleware handler of choice 
```

In case of a route match router middleware executes a handler attached to the route. If there is no match, next
middleware processes the request.
