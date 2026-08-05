# Upgrading Instructions for Yii Router

This file contains the upgrade notes for the Yii Router.
These notes highlight changes that could break your application when you upgrade it from one major version to another.

## 5.0.0

### Route and group builders

The immutable fluent APIs were moved from `Yiisoft\Router\Route` and `Yiisoft\Router\Group` to dedicated builder classes:

- `Yiisoft\Router\Builder\RouteBuilder`
- `Yiisoft\Router\Builder\GroupBuilder`

The static factory methods on `Route` and `Group` are retained as facades. They now return the corresponding builder
instead of a `Route` or `Group` data object.

- If you only use fluent route declarations such as `Route::get('/')->name('home')` and pass their results to
  `RouteCollectorInterface::addRoute()`, then no changes are required.
- If you type a result of `Route::get()`, `Route::post()`, `Route::methods()`, or another route factory as `Route`, then
  change the type to `RouteBuilder` or call `toRoute()` to obtain a `Route` data object.
- If you type a result of `Group::create()` as `Group`, then change the type to `GroupBuilder` or call `toRoute()` to
  obtain a `Group` data object.
- If you check a fluent factory result with `instanceof Route` or `instanceof Group`, then check for the corresponding
  builder instead, or call `toRoute()` before the check.
- If you call `getData()` on a fluent factory result, then call `toRoute()` and use an explicit getter on the resulting
  data object.
- If you want imports to reflect the actual types returned by the factories, then import the builders directly. Aliases
  allow route declarations to keep the familiar short names:

```php
use Yiisoft\Router\Builder\GroupBuilder as Group;
use Yiisoft\Router\Builder\RouteBuilder as Route;
```

Builders are immutable and can be passed directly to `RouteCollectorInterface::addRoute()`.

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

The fluent configuration methods moved to `RouteBuilder`. The static construction methods remain available on `Route`
as facades that return a `RouteBuilder`.

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

`Yiisoft\Router\Group` is now a mutable group data object with a public constructor. The fluent configuration methods
moved to `GroupBuilder`. The static `create()` method remains available on `Group` as a facade that returns a
`GroupBuilder`.

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

`CurrentRoute::getHost()` is deprecated but remains functional and returns the first route host.

- If you only need the first route host, then no changes are required.
- If you need all route hosts, then use `CurrentRoute::getHosts()`:

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
