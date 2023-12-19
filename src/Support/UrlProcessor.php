<?php

namespace Alnaggar\Turjuman\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;

/**
 * Class UrlProcessor
 *
 * The UrlProcessor class provides methods for processing URLs and routes, including extracting parameters, adding segments to the path, and extracting query parameters.
 *
 * @package Alnaggar\Turjuman
 */
class UrlProcessor
{
    /**
     * Extracts parameters from the given route and URL.
     *
     * @param \Illuminate\Routing\Route $route
     * @param string $url
     * @return array
     */
    public static function extractParameters(Route $route, string $url) : array
    {
        // Combine path and host parameters, then replace defaults.
        $parameters = self::extractPathParameters($route, $url);

        if ($route->getCompiled()->getHostRegex()) {
            $parameters = array_merge($parameters, self::extractHostParameters($route, $url));
        }

        return self::replaceDefaults($route, $parameters);
    }

    /**
     * Extracts query parameters from the given URL.
     *
     * @param string $url
     * @return array
     */
    public static function extractQueries(string $url) : array
    {
        // Extract query string.
        $queryString = parse_url($url, PHP_URL_QUERY);

        // Decode query string if it exists.
        $queryString = html_entity_decode($queryString ?: '');

        // Use parse_str to convert query string into an associative array.
        parse_str($queryString, $queries);

        return $queries;
    }

    /**
     * Adds a segment to the path at the specified index.
     *
     * @param string $path
     * @param string $segment
     * @param int $index
     * @return string
     */
    public static function addSegmentToPath(string $path, string $segment, int $index) : string
    {
        // Split the path into segments, insert the new segment at the specified index, and then join them back.
        $segments = explode('/', trim($path, '/'));
        array_splice($segments, max($index - 1, 0), 0, $segment);
        return trim(implode('/', $segments), '/');
    }

    /**
     * Extracts parameters from the path part of the URL.
     *
     * @param \Illuminate\Routing\Route $route
     * @param string $url
     * @return array
     */
    protected static function extractPathParameters(Route $route, string $url)
    {
        // Decode the raw URL and extract the path.
        $path = '/' . ltrim(rawurldecode(parse_url($url, PHP_URL_PATH) ?? ''), '/');

        // Use regex to match the path against the compiled regex of the route.
        preg_match($route->compiled->getRegex(), $path, $matches);

        // Match the parameter keys with the route's parameter names.
        return self::matchToKeys($route, array_slice($matches, 1));
    }

    /**
     * Extracts parameters from the host part of the URL.
     *
     * @param \Illuminate\Routing\Route $route
     * @param string $url
     * @return array
     */
    protected static function extractHostParameters(Route $route, string $url) : array
    {
        // Extract the host from the URL.
        $host = parse_url($url, PHP_URL_HOST) ?? '';

        // Use regex to match the host against the compiled host regex of the route.
        preg_match($route->compiled->getHostRegex(), $host, $matches);

        // Match the parameter keys with the route's parameter names.
        return self::matchToKeys($route, array_slice($matches, 1));
    }

    /**
     * Combines a set of parameter matches with the route's keys.
     *
     * @param \Illuminate\Routing\Route $route
     * @param array $matches
     * @return array
     */
    protected static function matchToKeys(Route $route, array $matches) : array
    {
        // If parameter names are empty, return an empty array.
        if (empty($parameterNames = $route->parameterNames())) {
            return [];
        }

        // Filter parameters by matching them with parameter names and removing empty values.
        $parameters = array_intersect_key($matches, array_flip($parameterNames));

        return array_filter($parameters, function ($value) {
            // Filter out non-string values and empty strings.
            return is_string($value) && strlen($value) > 0;
        });
    }

    /**
     * Replaces null parameters with their defaults.
     *
     * @param \Illuminate\Routing\Route $route
     * @param array $parameters
     * @return array
     */
    protected static function replaceDefaults(Route $route, array $parameters) : array
    {
        foreach ($parameters as $key => $value) {
            $parameters[$key] = $value ?? Arr::get($route->defaults, $key);
        }

        // Replace null parameters with their default values from the route.
        foreach ($route->defaults as $key => $value) {
            if (! isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }
}
