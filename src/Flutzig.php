<?php

namespace Uiibevy\Flutzig;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class Flutzig
{
    protected static $cache;

    protected string $url;
    protected mixed $group;
    protected $routes;

    /**
     * @throws ReflectionException
     */
    public function __construct($group = null, string $url = null)
    {
        $this->group = $group;

        $this->url = rtrim($url ?? url('/'), '/');

        if (!static::$cache) {
            static::$cache = $this->nameKeyedRoutes();
        }

        $this->routes = static::$cache;
    }

    /**
     * @throws ReflectionException
     */
    private function nameKeyedRoutes()
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

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'port' => parse_url($this->url)['port'] ?? null,
            'defaults' => method_exists(app('url'), 'getDefaultParameters')
                ? app('url')->getDefaultParameters()
                : [],
            'routes' => $this->applyFilters($this->group)->toArray(),
        ];
    }

    public function applyFilters($group)
    {
        if ($group) {
            return $this->group($group);
        }

        if (config()->has('flutzig.except')) {
            return $this->filter(config('flutzig.except'), false)->routes;
        }

        if (config()->has('flutzig.only')) {
            return $this->filter(config('flutzig.only'))->routes;
        }

        return $this->routes;
    }

    private function group($group): mixed
    {
        if (is_array($group)) {
            $filters = [];

            foreach ($group as $name) {
                $filters = array_merge($filters, Arr::wrap(config("flutzig.groups.$name")));
            }

            return $this->filter($filters)->routes;
        }

        if (config()->has("flutzig.groups.$group")) {
            return $this->filter(config("flutzig.groups.$group"))->routes;
        }

        return $this->routes;
    }

    public function filter($filters = [], $include = true): self
    {
        $filters = Arr::wrap($filters);

        $reject = collect($filters)->every(function (string $pattern) {
            return Str::startsWith($pattern, '!');
        });

        $this->routes = $reject
            ? $this->routes->reject(function ($route, $name) use ($filters) {
                foreach ($filters as $pattern) {
                    if (Str::is(substr($pattern, 1), $name)) {
                        return true;
                    }
                }
                return false;
            })
            : $this->routes->filter(function ($route, $name) use ($filters, $include) {
                if ($include === false) {
                    return !Str::is($filters, $name);
                }

                foreach ($filters as $pattern) {
                    if (Str::startsWith($pattern, '!') && Str::is(substr($pattern, 1), $name)) {
                        return false;
                    }
                }

                return Str::is($filters, $name);
            });

        return $this;
    }

    public static function clearRoutes(): void
    {
        static::$cache = null;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return array_merge($routes = $this->toArray(), [
            'defaults' => (object) $routes['defaults'],
        ]);
    }
}
