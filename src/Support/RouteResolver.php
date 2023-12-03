<?php

namespace Alnaggar\Turjuman\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\Request;

/**
 * Class RouteResolver
 *
 * The RouteResolver class provides methods for resolving and caching routes based on URL and method.
 * It includes functionality to find new routes, get routes by name, and get routes by URL and method.
 * The class utilizes a static cache to optimize route lookup for better performance.
 *
 * @package Alnaggar\Turjuman\Support
 */
class RouteResolver
{
    /**
     * Cached routes by URL and method.
     *
     * @var array<string, array<string, Route|null>>
     */
    protected static $cachedRoutes = [];

    /**
     * Get the new routes by comparing old and all routes.
     *
     * @param array<\Illuminate\Routing\Route> $oldRoutes An array of old routes.
     * @param array<\Illuminate\Routing\Route> $allRoutes An array of all routes.
     *
     * @return array<\Illuminate\Routing\Route> The new routes present in the all routes array but not in the old routes array.
     */
    public static function getNewRoutes(array $oldRoutes, array $allRoutes) : array
    {
        return array_udiff($allRoutes, $oldRoutes, fn ($a, $b) => $a <=> $b);
    }

    /**
     * Get a route by its name.
     *
     * @param string $name
     * @return Route|null
     */
    public static function getRouteByName(string $name) : ?Route
    {
        return Router::getRoutes()->getByName($name);
    }

    /**
     * Get a route by its URL and method.
     * Utilizes a static cache to optimize route lookup.
     *
     * @param string $url
     * @param string $method
     * @return Route|null
     */
    public static function getRouteByUrl(string $url, string $method) : ?Route
    {
        // Check if the route is already cached
        if (self::hasCachedRoute($url, $method)) {
            return self::getCachedRoute($url, $method);
        }

        // Create a request object for the given URL and method
        $request = Request::create($url, $method, server: Request::server());

        // Find the matching route
        $route = self::findMatchingRoute($request);

        // If the route matches but has a root URI, check if the provided URL has a non-root path
        // Because if the URL is external, the created request will be to the root URL
        if ($route && self::isExternalUrl($route, $url)) {
            $route = null;
        }

        // Cache the route for future lookups
        return self::cacheRoute($url, $method, $route);
    }

    /**
     * Check if the route for a given URL and method is cached.
     *
     * @param string $url
     * @param string $method
     * @return bool
     */
    protected static function hasCachedRoute(string $url, string $method) : bool
    {
        return isset(self::$cachedRoutes[$method][$url]);
    }

    /**
     * Get the cached route for a given URL and method.
     *
     * @param string $url
     * @param string $method
     * @return Route|null
     */
    protected static function getCachedRoute(string $url, string $method) : ?Route
    {
        return self::$cachedRoutes[$method][$url];
    }

    /**
     * Find the matching route for a given request.
     *
     * @param \Illuminate\Http\Request $request
     * @return Route|null
     */
    protected static function findMatchingRoute($request) : ?Route
    {
        return collect(Router::getRoutes()->get($request->getMethod()))
            ->filter(fn (Route $route) => ! $route->isFallback)
            ->first(fn (Route $route) => $route->matches($request));
    }

    /**
     * Check if the route has a root URI and the provided URL has a non-root path.
     *
     * @param Route|null $route
     * @param string $url
     * @return bool
     */
    protected static function isExternalUrl(Route $route, string $url) : bool
    {
        return $route->uri() === '/' && (parse_url($url, PHP_URL_PATH) ?? '/') !== '/';
    }

    /**
     * Cache the route for a given URL and method.
     *
     * @param string $url
     * @param string $method
     * @param Route|null $route
     * @return Route|null
     */
    protected static function cacheRoute(string $url, string $method, ?Route $route) : ?Route
    {
        return self::$cachedRoutes[$method][$url] = $route;
    }
}
