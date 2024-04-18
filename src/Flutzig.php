<?php

namespace Uiibevy\Flutzig;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @var Collection|null $cache cached routes
 */
class Flutzig implements Arrayable, Jsonable
{
    protected string $url;
    protected ?string $group;
    protected Collection $routes;

    /**
     * Constructs a new instance of the class.
     *
     * @param  string|null  $group  The group associated with the instance.
     * @param  string|null  $url  The URL associated with the instance.
     * @return void
     * @throws ReflectionException
     */
    public function __construct(?string $group = null, ?string $url = null)
    {
        $this->group = $group;

        $this->url = rtrim($url ?? url('/'), '/');

        $this->routes = $this->nameKeyedRoutes();
    }

    /**
     * Returns a collection of routes indexed by their names.
     *
     * @return Collection
     * @throws ReflectionException
     */
    private function nameKeyedRoutes(): Collection
    {
        [$fallbacks, $routes] = collect(app('router')->getRoutes()->getRoutesByName())
            ->reject(function ($route) {
                return Str::startsWith($route->getName(), 'generated::');
            })
            ->partition(function ($route) {
                return $route->isFallback;
            });

        $bindings = $this->resolveBindings($routes->toArray());

        $fallbacks->map(function ($route, $name) use ($routes) {
            $routes->put($name, $route);
        });

        return $routes->map(function ($route) use ($bindings) {
            return collect($route)->only(['uri', 'methods', 'wheres'])
                ->put('domain', $route->domain())
                ->put('parameters', $route->parameterNames())
                ->put('bindings', $bindings[$route->getName()] ?? [])
                ->when($middleware = config('flutzig.middleware'), function ($collection) use ($middleware, $route) {
                    if (is_array($middleware)) {
                        return $collection->put('middleware',
                            collect($route->middleware())->intersect($middleware)->values()->all());
                    }

                    return $collection->put('middleware', $route->middleware());
                })->filter();
        });
    }

    /**
     * Resolve the bindings for the routes.
     *
     * @param  array  $routes  The routes to resolve bindings for.
     * @return array The routes with resolved bindings.
     * @throws ReflectionException
     */
    private function resolveBindings(array $routes): array
    {
        $scopedBindings = method_exists(head($routes) ?: '', 'bindingFields');

        foreach ($routes as $name => $route) {
            $bindings = [];

            foreach ($route->signatureParameters(UrlRoutable::class) as $parameter) {
                if (!in_array($parameter->getName(), $route->parameterNames())) {
                    break;
                }

                $model = class_exists(Reflector::class)
                    ? Reflector::getParameterClassName($parameter)
                    : $parameter->getType()->getName();
                $override = (new ReflectionClass($model))->isInstantiable() && (
                        (new ReflectionMethod($model, 'getRouteKeyName'))->class !== Model::class
                        || (new ReflectionMethod($model, 'getKeyName'))->class !== Model::class
                        || (new ReflectionProperty($model, 'primaryKey'))->class !== Model::class
                    );

                // Avoid booting this model if it doesn't override the default route key name
                $bindings[$parameter->getName()] = $override ? app($model)->getRouteKeyName() : 'id';
            }

            $routes[$name] = $scopedBindings ? array_merge($bindings, $route->bindingFields()) : $bindings;
        }

        return $routes;
    }

    /**
     * Converts the current object to an array.
     *
     * @return array The converted array representation of the object.
     */
    #[ArrayShape(['url' => "mixed", 'port' => "mixed|null", 'defaults' => "mixed", 'routes' => "mixed[]"])]
    public function toArray(): array
    {
        $url = app('url');

        return [
            'url' => $this->url,
            'port' => parse_url($this->url)['port'] ?? null,
            'defaults' => $url instanceof UrlGenerator ? $url->getDefaultParameters() : [],
            'routes' => $this->applyFilters($this->group)->toArray(),
        ];
    }

    /**
     * Applies filters to the routes and returns a collection of filtered routes.
     * If a group of routes is specified, only the routes within that group will be returned.
     * If the 'except' configuration is set, filters out the specified routes from the collection.
     * If the 'only' configuration is set, filters the collection to include only the specified routes.
     *
     * @param  string|null  $group  The group name of routes to filter (optional)
     * @return Collection The filtered collection of routes
     */
    public function applyFilters(?string $group): Collection
    {
        if ($group) {
            return $this->group($group);
        }

        if (config()->has('flutzig.except')) {
            return $this->filter(config('flutzig.except'))->routes;
        }

        if (config()->has('flutzig.only')) {
            return $this->filter(config('flutzig.only'))->routes;
        }

        return $this->routes;
    }

    /**
     * Group the routes by applying filters based on the given group.
     *
     * @param  array|string  $group  The group(s) to apply filters for.
     * @return Collection The filtered routes.
     */
    private function group(array|string $group): Collection
    {
        if (is_array($group)) {
            $filters = [];

            $filters = collect($group)->map(function ($group, $name) use ($filters) {
                array_merge($filters, Arr::wrap(config("flutzig.groups.$name", [])));
            });

            return $this->filter($filters)->routes;
        }

        if (config()->has("flutzig.groups.$group")) {
            return $this->filter(config("flutzig.groups.$group", []))->routes;
        }

        return $this->routes;
    }

    /**
     * Apply filters to the routes.
     *
     * @param  Collection|array|string  $filters  The filters to be applied.
     * @return self  The current instance of the class.
     */
    public function filter(Collection|array|string $filters = []): self
    {
        if (!($filters instanceof Collection)) {
            $filters = collect(Arr::wrap($filters));
        }
        $this->routes = $filters->every(fn(string $pattern) => str($pattern)->startsWith('!'))
            ? $this->routes->reject(fn($route) => $filters->contains($route))
            : $this->routes->filter(function ($route, $name) use ($filters) {
                if ($filters->contains(
                    fn($pattern) => str($name)->startsWith($pattern) && str($name)->is(substr($pattern, 1))
                )) {
                    return false;
                }
                return str($name)->is($filters);
            });
        return $this;
    }

    /**
     * Create a new instance of the class using optional parameters.
     *
     * @param  string|null  $group  The group parameter.
     * @param  string|null  $url  The URL parameter.
     *
     * @return static A new instance of the class.
     * @throws ReflectionException
     */
    public static function from(?string $group = null, ?string $url = null): static
    {
        return new static($group, $url);
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options  Bitmask of JSON encode options.
     * @return string  A JSON string representing the object.
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Converts the object to a JSON serializable array.
     *
     * @return array The JSON serializable array representation of the object.
     */
    public function jsonSerialize(): array
    {
        return array_merge($routes = $this->toArray(), [
            'defaults' => (object) $routes['defaults'],
        ]);
    }
}
