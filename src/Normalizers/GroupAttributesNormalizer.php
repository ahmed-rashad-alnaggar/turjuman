<?php

namespace Alnaggar\Turjuman\Normalizers;

use Alnaggar\Turjuman\Contracts\GroupAttributeNormalizerInterface;
use Alnaggar\Turjuman\GroupAttributes;

/**
 * Class GroupAttributesNormalizer
 *
 * This class implements the GroupAttributeNormalizerInterface and provides a method to normalize a set of group attributes.
 * The normalization process involves using specific normalizer classes for 'supported_locales', 'default_locale', and 'route_aliases'.
 * Default values for 'display_location' and 'hide_default' are set if not provided in the input attributes, falling back to the corresponding values in the fallback attributes.
 *
 * @package Alnaggar\Turjuman
 */
class GroupAttributesNormalizer implements GroupAttributeNormalizerInterface
{
    /**
     * Normalize group attributes.
     * 
     * @param array<string, mixed> $attributes
     * @param \Alnaggar\Turjuman\GroupAttributes|null $fallbackAttributes
     * 
     * @throws \Alnaggar\Turjuman\Exceptions\UnsupportedLocaleException
     * 
     * @return array<string, mixed>
     */
    public static function normalize(array $attributes, ?GroupAttributes $fallbackAttributes) : array
    {
        // Normalization using specific normalizer classes
        $attributes['supported_locales'] = SupportedLocalesNormalizer::normalize($attributes, $fallbackAttributes);
        $attributes['default_locale'] = DefaultLocaleNormalizer::normalize($attributes, $fallbackAttributes);
        $attributes['route_aliases'] = RouteAliasesNormalizer::normalize($attributes, $fallbackAttributes);

        // Default values if not provided
        if (! array_key_exists('display_location', $attributes)) { // The null coalescing operator is not applicable in this case as 'display_location' is nullable.
            $attributes['display_location'] = $fallbackAttributes->getDisplayLocation();
        }
        $attributes['hide_default'] ??= $fallbackAttributes->isHideDefault();
        $attributes['locale_identifier'] ??= $fallbackAttributes->getLocaleIdentifier();

        return $attributes;
    }
}