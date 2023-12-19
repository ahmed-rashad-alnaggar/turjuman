<?php

namespace Alnaggar\Turjuman\Normalizers;

use Alnaggar\Turjuman\Contracts\GroupAttributeNormalizerInterface;
use Alnaggar\Turjuman\GroupAttributes;

/**
 * Class RouteAliasesNormalizer
 *
 * This class implements the GroupAttributeNormalizerInterface and provides a method to normalize 'route_aliases' in group attributes.
 * The normalization process involves parsing URLs and organizing them into a functional form.
 * If 'route_aliases' is not provided in the input attributes, it falls back to the 'route_aliases' in the fallback attributes.
 *
 * @package Alnaggar\Turjuman
 */
class RouteAliasesNormalizer implements GroupAttributeNormalizerInterface
{
    /**
     * Normalize the provided URLs to their functional form.
     *
     * @param array<string, mixed> $attributes
     * @param \Alnaggar\Turjuman\GroupAttributes|null $fallbackAttributes
     *
     * @return array<string, array<string, string>>
     */
    public static function normalize(array $attributes, ?GroupAttributes $fallbackAttributes) : array
    {
        if (! array_key_exists('route_aliases', $attributes)) {
            return $fallbackAttributes->getRouteAliases();
        }

        return array_map(function ($routesOrDomain) {
            if (is_array($routesOrDomain)) {
                return self::normalizeRoutes($routesOrDomain);
            } else {
                return trim(parse_url($routesOrDomain, PHP_URL_HOST) ?? $routesOrDomain, '/');
            }
        }, $attributes['route_aliases']);
    }

    /**
     * Normalize the routes by parsing URLs and organizing them into a functional form.
     *
     * @param array<string, string> $routes
     * @return array<string, array<string, string>>
     */
    protected static function normalizeRoutes(array $routes) : array
    {
        $normalizedRoutes = [];

        foreach ($routes as $route => $alias) {
            $routeUrl = trim(parse_url($route, PHP_URL_PATH) ?? '/', '/');
            $routeDomain = parse_url($route, PHP_URL_HOST);

            if (is_string($alias)) {
                $aliasUrl = parse_url($alias, PHP_URL_PATH);
                $aliasDomain = parse_url($alias, PHP_URL_HOST);

                if ($aliasUrl) {
                    $aliasUrl = trim($aliasUrl, '/') ?: '/';
                }

                $alias = [
                    'url' => $aliasUrl,
                    'domain' => $aliasDomain
                ];
            }

            $normalizedRoutes[$routeDomain . $routeUrl] = $alias;
        }

        return $normalizedRoutes;
    }
}
