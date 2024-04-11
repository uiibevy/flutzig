![Flutzig - Use your Laravel routes in Flutter](https://raw.githubusercontent.com/uiibevy/flutzig/main/flutzig-banner.png)

# Flutzig – Use your Laravel routes in Flutter

[![GitHub Actions Status](https://img.shields.io/github/actions/workflow/status/uiibevy/flutzig/test.yml?branch=main&style=flat)](https://github.com/uiibevy/flutzig/actions?query=workflow:Tests+branch:main)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/uiibevyco/flutzig.svg?style=flat)](https://packagist.org/packages/uiibevyco/flutzig)
[![Downloads on Packagist](https://img.shields.io/packagist/dt/uiibevyco/flutzig.svg?style=flat)](https://packagist.org/packages/uiibevyco/flutzig)
[![Latest Version on NPM](https://img.shields.io/npm/v/flutzig-js.svg?style=flat)](https://npmjs.com/package/flutzig-js)
[![Downloads on NPM](https://img.shields.io/npm/dt/flutzig-js.svg?style=flat)](https://npmjs.com/package/flutzig-js)

flutzig provides a JavaScript `route()` function that works like Laravel's, making it a breeze to use your named Laravel routes in JavaScript.

- [**Installation**](#installation)
- [**Usage**](#usage)
  - [`route()` function](#route-function)
  - [`Router` class](#router-class)
  - [Route-model binding](#route-model-binding)
  - [TypeScript](#typescript)
- [**JavaScript frameworks**](#javascript-frameworks)
  - [Generating and importing flutzig's configuration](#generating-and-importing-flutzigs-configuration)
  - [Importing the `route()` function](#importing-the-route-function)
  - [Vue](#vue)
  - [React](#react)
  - [SPAs or separate repos](#spas-or-separate-repos)
- [**Filtering Routes**](#filtering-routes)
  - [Including/excluding routes](#includingexcluding-routes)
  - [Filtering with groups](#filtering-with-groups)
- [**Other**](#other)
- [**Contributing**](#contributing)

## Installation

Install Flutzig in your Laravel app with Composer:

```bash
composer require uiibevy/flutzig
```

Install Flutzig in your flutter app with pub:
```bash
pub get flutzig
```

Add the Flutzig initialisation in your Flutter app

```dart
void main() async {
  await Flutzig.init();
  runApp(const MyApp());
}
```

## Usage

### `route()` function

flutzig's `route()` function works like [Laravel's `route()` helper](https://laravel.com/docs/11.x/helpers#method-route)—you can pass it the name of a route, and the parameters you want to pass to the route, and it will generate a URL.

#### Basic usage

```php
Route::get('posts', fn (Request $request) => /* ... */)->name('posts.index');
```

```dart
route(name: 'posts.index'); // 'https://flutzig.test/posts'
```

#### Parameters

```php
Route::get('posts/{post}', fn (Post $post) => /* ... */)->name('posts.show');
```

```dart
route(name: 'posts.show', params: { post: 1 }); // 'https://flutzig.test/posts/1'
```

#### Multiple parameters

```php
Route::get('venues/{venue}/events/{event}', fn (Venue $venue, Event $event) => /* ... */)
    ->name('venues.events.show');
```

```dart
route(name: 'venues.events.show', params: { venue: 1, event: 2 }); // 'https://flutzig.test/venues/1/events/2'
```

#### Query parameters

flutzig adds arguments that don't match any named route parameters as query parameters:

```php
Route::get('venues/{venue}/events/{event}', fn (Venue $venue, Event $event) => /* ... */)
    ->name('venues.events.show');
```

```dart
route(name: 'venues.events.show', params: {
    venue: 1,
    event: 2,
    page: 5,
    count: 10,
});
// 'https://flutzig.test/venues/1/events/2?page=5&count=10'
```

If you need to pass a query parameter with the same name as a route parameter, nest it under the special `query_` key:

```dart
route(name: 'venues.events.show', params: {
    venue: 1,
    event: 2,
    query_: {
        event: 3,
        page: 5,
    },
});
// 'https://flutzig.test/venues/1/events/2?event=3&page=5'
```

Like Laravel, Flutzig automatically encodes boolean query parameters as integers in the query string:

```dart
route(name: 'venues.events.show', params: {
    venue: 1,
    event: 2,
    query_: {
        draft: false,
        overdue: true,
    },
});
// 'https://flutzig.test/venues/1/events/2?draft=0&overdue=1'
```

#### Default parameter values

flutzig supports default route parameter values ([Laravel docs](https://laravel.com/docs/urls#default-values)).

```php
Route::get('{locale}/posts/{post}', fn (Post $post) => /* ... */)->name('posts.show');
```

```php
// app/Http/Middleware/SetLocale.php

URL::defaults(['locale' => $request->user()->locale ?? 'de']);
```

```dart
route(name: 'posts.show', params: 1); // 'https://flutzig.test/de/posts/1'
```

#### Examples

HTTP request with `dio`:

```dart
final post = { 'id': 1, 'title': 'Flutzig Stardust' };
final response = await Dio.get(route('posts.show', post));
print(response);
```

### `Router` class

Calling flutzig's `route()` function with no arguments will return an instance of its JavaScript `Router` class, which has some other useful properties and methods.

#### Check the current route: `route().current()`

COMING SOON !

#### Check if a route exists: `route().has()`

COMING SOON !

### Route-model binding

Flutzig supports Laravel's [route-model binding](https://laravel.com/docs/routing#route-model-binding), and can even recognize custom route key names. If you pass `route()` a Dart object as a route parameter, Flutzig will use the registered route-model binding keys for that route to find the correct parameter value inside the object. If no route-model binding keys are explicitly registered for a parameter, Flutzig will use the object's `id` key.

```php
// app/Models/Post.php

class Post extends Model
{
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
```

```php
Route::get('blog/{post}', function (Post $post) {
    return view('posts.show', ['post' => $post]);
})->name('posts.show');
```

```dart
final post = {
    id: 3,
    title: 'Introducing flutzig v1',
    slug: 'introducing-flutzig-v1',
    date: '2020-10-23T20:59:24.359278Z',
};

// Flutzig knows that this route uses the 'slug' route-model binding key:

route(name: 'posts.show', params: post); // 'https://flutzig.test/blog/introducing-flutzig-v1'
```

flutzig also supports [custom keys](https://laravel.com/docs/routing#customizing-the-key) for scoped bindings declared directly in a route definition:

```php
Route::get('authors/{author}/photos/{photo:uuid}', fn (Author $author, Photo $photo) => /* ... */)
    ->name('authors.photos.show');
```

```dart
final photo = {
    uuid: '714b19e8-ac5e-4dab-99ba-34dc6fdd24a5',
    filename: 'sunset.jpg',
}

route(name: 'authors.photos.show', params: [{ id: 1, name: 'Ansel' }, photo]);
// 'https://flutzig.test/authors/1/photos/714b19e8-ac5e-4dab-99ba-34dc6fdd24a5'
```

### Generating and importing flutzig's configuration

Flutzig provides an Artisan command to output its config and routes to a file:

```bash
php artisan flutzig:generate
```

This command places your configuration in `storage/public/flutzig/routes.json` by default, but you can customize this path by passing an argument to the Artisan command or setting the `flutzig.output.path` config value.

The file `flutzig:generate` creates looks something like this:

```dart
// storage/public/flutzig/routes.json
{
    url: 'https://flutzig.test',
    port: null,
    routes: {
        home: {
            uri: '/',
            methods: [ 'GET', 'HEAD'],
            domain: null,
        },
        login: {
            uri: 'login',
            methods: ['GET', 'HEAD'],
            domain: null,
        },
    },
};
```

## Filtering Routes

Flutzig supports filtering the list of routes it outputs, which is useful if you have certain routes that you don't want to be included and visible in your json export.

> [!IMPORTANT]
> Hiding routes from flutzig's output is not a replacement for thorough authentication and authorization. Routes that should not be accessibly publicly should be protected by authentication whether they're filtered out of flutzig's output or not.

### Including/excluding routes

To set up route filtering, create a config file in your Laravel app at `config/flutzig.php` and add **either** an `only` or `except` key containing an array of route name patterns.

> Note: You have to choose one or the other. Setting both `only` and `except` will disable filtering altogether and return all named routes.

```php
// config/flutzig.php

return [
    'only' => ['home', 'posts.index', 'posts.show'],
];
```

You can use asterisks as wildcards in route filters. In the example below, `admin.*` will exclude routes named `admin.login`, `admin.register`, etc.:

```php
// config/flutzig.php

return [
    'except' => ['_debugbar.*', 'horizon.*', 'admin.*'],
];
```

### Filtering with groups

You can also define groups of routes that you want make available in different places in your app, using a `groups` key in your config file:

```php
// config/flutzig.php

return [
    'groups' => [
        'admin' => ['admin.*', 'users.*'],
        'author' => ['posts.*'],
    ],
];
```

## Contributing

To get started contributing to Flutzig, check out [the contribution guide](CONTRIBUTING.md).

## Credits

- [Hicham SADDEK](https://linkedin.com/in/hicham-saddek)
- [All contributors](https://github.com/uiibevy/flutzig/contributors)

## License

Flutzig is open-source software released under the MIT license. See [LICENSE](LICENSE) for more information.
