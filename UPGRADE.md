# Upgrading Instructions for Yii Router

This file contains the upgrade notes for the Yii Router.
These notes highlight changes that could break your application when you upgrade it from one major version to another.

## 4.0.3

Static route and group factories are deprecated but remain available. Replace them with constructors:

| Deprecated factory | Replacement |
| --- | --- |
| `Route::get($pattern)` | `new Get($pattern)` |
| `Route::post($pattern)` | `new Post($pattern)` |
| `Route::put($pattern)` | `new Put($pattern)` |
| `Route::delete($pattern)` | `new Delete($pattern)` |
| `Route::patch($pattern)` | `new Patch($pattern)` |
| `Route::head($pattern)` | `new Head($pattern)` |
| `Route::options($pattern)` | `new Options($pattern)` |
| `Route::methods($methods, $pattern)` | `new Route(pattern: $pattern, methods: $methods)` |
| `Group::create($prefix)` | `new Group($prefix)` |

Import the method-specific classes from `Yiisoft\Router\Route`, and `Route` and `Group` from `Yiisoft\Router`.
Configuration can be passed as named constructor arguments. Existing immutable configuration methods remain available.
When chaining methods after a constructor, use parentheses, for example `(new Get('/'))->name('home')`,
to support PHP 8.1.

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
