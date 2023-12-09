<?php

namespace Alnaggar\Turjuman;

use Illuminate\Support\Facades\Facade;

/**
 * @method static Group group(\Closure $routes, array $attributes = [])
 * @method static void ignore(\Closure|\Illuminate\Routing\Route $routes)
 * @method static Localizer addConfigLocales(array $locales)
 * @method static Localizer setConfigLocales(array $locales)
 * @method static Localizer setConfigDefaultLocale(string $locale)
 * @method static Localizer setConfigAttributes(GroupAttributes $attributes)
 * @method static Locale setCurrentLocale(string $locale)
 * @method static GroupAttributes getConfigAttributes()
 * @method static GroupAttributes getCurrentAttributes()
 * @method static Group|null getCurrentGroup()
 * @method static Locale|null getCurrentLocale()
 * @method static Locale|null getLocale(string $locale)
 * @method static array getSupportedLocales()
 * @method static Locale getDefaultLocale()
 * @method static array getLocalesByProperty(string $property, mixed $default = null)
 * @method static string|null getLocalizedUrl(string $url, string|null $locale = null)
 * @method static string|null getNonLocalizedUrl(string $url)
 * @method static string getLocalizedPagePath(string $path, string|null $locale = null)
 * @method static bool isLocalizedUrl(string $url, string $method = 'GET')
 * @method static bool isLocalizedRoute(\Illuminate\Routing\Route|string|null $route = null)
 * @method static bool isSupportedLocale(string $locale)
 * @method static bool isCurrentLocale(string $locale)
 * @method static bool isDefaultLocale(string $locale)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 * 
 * @see \Alnaggar\Turjuman\Localizer
 */
class Turjuman extends Facade
{
    protected static function getFacadeAccessor() : string
    {
        return 'turjuman';
    }
}