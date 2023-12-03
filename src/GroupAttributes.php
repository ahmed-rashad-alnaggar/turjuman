<?php

namespace Alnaggar\Turjuman;

use Alnaggar\Turjuman\Normalizers\GroupAttributesNormalizer;
use Alnaggar\Turjuman\Traits\Extensible;

/**
 * GroupAttributes class represents the attributes configuration for route groups in Turjuman.
 *
 * This class encapsulates the configuration attributes for route groups, providing a normalized
 * and extensible way to access and manage these attributes by utilizing the Extensible trait.
 *
 * @package Alnaggar\Turjuman
 */
class GroupAttributes implements \ArrayAccess
{
    use Extensible;

    /**
     * Locales Aliases.
     * 
     * @var array<string, string>
     */
    protected $localesAliases;

    /**
     * Creates a new GroupAttributes instance.
     *
     * @param array<string, mixed> $attributes An associative array of attribute/value pairs.
     * The array must include essential attributes which are
     * ['supported_locales', 'default_locale', 'display_location', 'hide_default', 'routes_aliases'].
     * Any additional entries will be treated as extra attributes like [name, region],
     * accessible via the Extensible trait access methods.
     * @param \Alnaggar\Turjuman\GroupAttributes|null $fallbackAttributes The system will fallback to these attributes to determine missing ones.
     * If $fallbackAttributes is null, this instance will be treated as the configuration one.
     * @return void
     */
    public function __construct(array $attributes, ?GroupAttributes $fallbackAttributes = null)
    {
        $this->propertyBag = GroupAttributesNormalizer::normalize($attributes, $fallbackAttributes);

        $this->immutableBag = ['supported_locales', 'default_locale', 'display_location', 'hide_default', 'routes_aliases'];
    }

    /**
     * Return group supported locales.
     * 
     * @return array<string, \Alnaggar\Turjuman\Locale>
     */
    public function getSupportedLocales() : array
    {
        return $this->propertyBag['supported_locales'];
    }

    /**
     * Return group default locale.
     * 
     * @return \Alnaggar\Turjuman\Locale
     */
    public function getDefaultLocale() : Locale
    {
        return $this->propertyBag['default_locale'];
    }

    /**
     * Return group display location.
     * 
     * @return int|string|null
     */
    public function getDisplayLocation() : int|string|null
    {
        return $this->propertyBag['display_location'];
    }

    /**
     * Return group routes aliases.
     * 
     * @return array<string, array<string, string>>
     */
    public function getRoutesAliases() : array
    {
        return $this->propertyBag['routes_aliases'];
    }

    /**
     * Return an associative array of code/property.
     * 
     * @param string $property The property to retrieve.
     * @param mixed $default A value to be returned if property not exists on a certian locale instance.
     * If a clouser is passed, the locale instance will be provided as a parameter. 
     * 
     * @return array<string, mixed> An associative array with locale codes as keys and corresponding property values.
     */
    public function getLocalesByProperty(string $property, mixed $default = null) : array
    {
        return array_map(
            fn (Locale $locale) => $locale->hasProperty($property) ? $locale[$property] : value($default, $locale),
            $this->getSupportedLocales()
        );
    }

    /**
     * Return locales aliases.
     * If locale does not have an alias its code is return instead.
     * 
     * @return array<string, string>
     */
    public function getLocalesAliases() : array
    {
        return $this->localesAliases ??=
            $this->getLocalesByProperty('alias', fn (Locale $locale) => $locale->getCode());
    }

    /**
     * Returns all group attributes.
     * 
     * @return array<string, mixed>
     */
    public function getAllAttributes() : array
    {
        return $this->propertyBag;
    }

    /**
     * Determine if should hide the default locale or not.
     * 
     * @return bool
     */
    public function isHideDefault() : bool
    {
        return $this->propertyBag['hide_default'];
    }

    /**
     * Determine if the locale display type in the URL is set to segment.
     *
     * This function returns true if the locale display type is set to segment,
     * allowing different behavior in handling localized URLs based on segments.
     *
     * @return bool True if the locale display type is set to segment; otherwise, false.
     */
    public function isLocaleDisplayTypeSegment() : bool
    {
        return is_int($this->getDisplayLocation());
    }


    /**
     * Determine if the locale display type in the URL is set to query parameter.
     *
     * This function returns true if the locale display type is set to query parameter,
     * allowing different behavior in handling localized URLs based on query parameters.
     *
     * @return bool True if the locale display type is set to query parameter; otherwise, false.
     */
    public function isLocaleDisplayTypeQuery() : bool
    {
        return is_string($this->getDisplayLocation());
    }

    /**
     * Determine if the locale display type in the URL is set to hidden.
     *
     * This function returns true if the locale display type is set to hidden,
     * indicating that no explicit display method (segment or query) is used.
     *
     * @return bool True if the locale display type is set to hidden; otherwise, false.
     */
    public function isLocaleDisplayTypeHidden() : bool
    {
        return ! $this->isLocaleDisplayTypeSegment() && ! $this->isLocaleDisplayTypeQuery();
    }

    /**
     * Checks whether an attribute exists or not.
     * 
     * @param string $attribute
     * @return bool
     */
    public function hasAttribute(string $attribute) : bool
    {
        return $this->offsetExists($attribute);
    }
}