# Upgrading Instructions for Yii Router

This file contains the upgrade notes for the Yii Router.
These notes highlight changes that could break your application when you upgrade it from one major version to another.

## 5.0.0

### Route and group builders

The immutable fluent APIs were moved from `Yiisoft\Router\Route` and `Yiisoft\Router\Group` to dedicated builder classes:

- `Yiisoft\Router\Builder\RouteBuilder`
- `Yiisoft\Router\Builder\GroupBuilder`

Update imports while keeping aliases if you want existing route declarations to remain unchanged:

```php
// Before
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

// After
use Yiisoft\Router\Builder\GroupBuilder as Group;
use Yiisoft\Router\Builder\RouteBuilder as Route;
```

Code such as `Route::get('/')->name('home')` and `Group::create('/api')->routes(...)` then continues to use the same
fluent syntax. Builders are immutable and can be passed directly to `RouteCollectorInterface::addRoute()`.

### `Route` changes

`Yiisoft\Router\Route` is now a mutable route data object with a public constructor:

```php
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

$route = new Route(
    methods: [Method::GET],
    pattern: '/',
    name: 'home',
    action: HomeAction::class,
);
```

The static construction and fluent configuration methods moved to `RouteBuilder`.

`Route::getData()` was removed. Replace it with the corresponding explicit method:

| Before | After |
|---|---|
| `getData('name')` | `getName()` |
| `getData('pattern')` | `getPattern()` |
| `getData('hosts')` | `getHosts()` |
| `getData('methods')` | `getMethods()` |
| `getData('defaults')` | `getDefaults()` |
| `getData('override')` | `isOverride()` |
| `getData('enabledMiddlewares')` | `getEnabledMiddlewares()` |

The action is stored separately from route middleware. Use `getAction()` for the action,
`getEnabledMiddlewares()` for filtered middleware only, or `getEnabledMiddlewaresAndAction()` for the dispatch pipeline.
Mutable configuration is available through `setMethods()`, `setPattern()`, `setName()`, `setAction()`,
`setMiddlewares()`, `setDefaults()`, `setHosts()`, `setOverride()`, and `setDisabledMiddlewares()`.

### `Group` changes

`Yiisoft\Router\Group` is now a mutable group data object with a public constructor. The static `create()` method and
fluent configuration methods moved to `GroupBuilder`.

`Group::getData()` was removed. Replace it with the corresponding explicit method:

| Before | After |
|---|---|
| `getData('prefix')` | `getPrefix()` |
| `getData('namePrefix')` | `getNamePrefix()` |
| `getData('hosts')` | `getHosts()` |
| `getData('routes')` | `getRoutes()` |
| `getData('corsMiddleware')` | `getCorsMiddleware()` |
| `getData('enabledMiddlewares')` | `getEnabledMiddlewares()` |

Mutable configuration is available through `setPrefix()`, `setNamePrefix()`, `setRoutes()`, `setMiddlewares()`,
`setHosts()`, `setCorsMiddleware()`, and `setDisabledMiddlewares()`.

### `RoutableInterface`

`RoutableInterface` was added for custom route definitions. Its `toRoute()` method must return a `Route` or `Group`.
The route collector and groups accept routable instances in addition to route and group data objects. The route
collection clones the returned object before applying collection middleware or group transformations.

### `RouteCollectorInterface` changes

`RouteCollectorInterface::addRoute()` now also accepts `RoutableInterface` instances.

`RouteCollectorInterface::getMiddlewareDefinitions()` was renamed:

```php
// Before
$collector->getMiddlewareDefinitions();

// After
$collector->getMiddlewares();
```

### `CurrentRoute` changes

`CurrentRoute::getHost()` was replaced by `CurrentRoute::getHosts()` and now returns all route hosts:

```php
// Before
$host = $currentRoute->getHost();

// After
$hosts = $currentRoute->getHosts();
```

## 4.0.0

### `Route`, `Group` and `MatchingResult` changes

In this release classes `Route`, `Group` and `MatchingResult` are made dispatcher-independent. Now you don't can inject
own middleware dispatcher to group or to route.

The following backward incompatible changes have been made.

#### `Route`

- Removed parameter `$dispatcher` from `Route` creating methods: `get()`, `post()`, `put()`, `delete()`, `patch()`,
  `head()`, `options()`, `methods()`.
- Removed methods `Route::injectDispatcher()` and `Route::withDispatcher()`.
- `Route::getData()` changes:
  - removed elements `dispatcherWithMiddlewares` and `hasDispatcher`;
  - added element `enabledMiddlewares`.
 
#### `Group`

- Removed parameter `$dispatcher` from `Group::create()` method.
- Removed method `Group::withDispatcher()`.
- `Group::getData()` changes:
  - removed element `hasDispatcher`;
  - key `items` renamed to `routes`;
  - key `middlewareDefinitions` renamed to `enabledMiddlewares`.

#### `MatchingResult`

- Removed `MatchingResult` implementation from `MiddlewareInterface`, so it is no longer middleware.
- Removed method `MatchingResult::process()`.

### `UrlGeneratorInterface` changes

Contract is changed:

- on URL generation all unused arguments must be moved to query parameters, if query parameter with 
such name doesn't exist;
- added `$hash` parameter to `generate()`, `generateAbsolute()` and `generateFromCurrent()` methods.

You should change your interface implementations accordingly.
