# Upgrading Instructions for Yii Router

This file contains the upgrade notes for the Yii Router.
These notes highlight changes that could break your application when you upgrade it from one major version to another.

## 4.0.0

In this release classes `Route`, `Group` and `MatchingResult` are made dispatcher independent. Now you don't can inject
own middleware dispatcher to group or to route.

The following backward incompatible changes have been made.

### `Route`

- Removed parameter `$dispatcher` from `Route` creating methods: `get()`, `post()`, `put()`, `delete()`, `patch()`,
  `head()`, `options()`, `methods()`.
- Removed methods `Route::injectDispatcher()` and `Route::withDispatcher()`.
- `Route::getData()` changes:
  - removed elements `dispatcherWithMiddlewares` and `hasDispatcher`;
  - added element `enabledMiddlewares`.
 
### `Group`

- Removed parameter `$dispatcher` from `Group::create()` method.
- Removed method `Group::withDispatcher()`.
- `Group::getData()` changes:
  - removed element `hasDispatcher`;
  - key `items` renamed to `routes`;
  - key `middlewareDefinitions` renamed to `enabledMiddlewares`.

### `MatchingResult`

- Removed `MatchingResult` implementation from `MiddlewareInterface`, so it is no longer middleware.
- Removed method `MatchingResult::process()`.
