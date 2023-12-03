<?php

namespace Alnaggar\Turjuman\Normalizers;

use Alnaggar\Turjuman\Contracts\GroupAttributeNormalizerInterface;
use Alnaggar\Turjuman\GroupAttributes;

/**
 * Class RoutesAliasesNormalizer
 *
 * Routes aliases normalizer responsible for normalizing the provided URLs to their functional form.
 * This class implements the GroupAttributeNormalizerInterface and provides a method to normalize 'routes_aliases' in group attributes.
 * It processes the input array of route aliases per locale, validating and transforming the URLs into a functional form.
 * The normalized result is an array mapping route hosts and paths to their corresponding aliases.
 * If 'routes_aliases' is not provided in the input attributes, it falls back to the 'routes_aliases' in the fallback attributes.
 *
 * @package Alnaggar\Turjuman\Normalizers
 */
class RoutesAliasesNormalizer implements GroupAttributeNormalizerInterface
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
        if (isset($attributes['routes_aliases'])) {
            return array_map(
                function ($routesAliasesPerLocale) {
                    return array_reduce(
                        array_keys($routesAliasesPerLocale),
                        function ($carry, $item) use ($routesAliasesPerLocale) {
                            $route = parse_url($item);
                            $alias = parse_url($routesAliasesPerLocale[$item]);

                            $routeHost = $route['host'] ?? '';
                            $routePath = $route['path'] ?? '';
                            $aliasPath = $alias['path'] ?? '';

                            $routePath = $routePath !== '' ? trim($routePath, '/') : '';
                            $aliasPath = $aliasPath !== '' ? trim($aliasPath, '/') : '';

                            if ($routePath === '' || count(explode('/', $routePath)) !== count(explode('/', $aliasPath))) {
                                return $carry;
                            }

                            return $carry + [$routeHost . $routePath => $aliasPath];
                        },
                        []
                    );
                },
                $attributes['routes_aliases']
            );
        }

        return $fallbackAttributes->getRoutesAliases();
    }
}
