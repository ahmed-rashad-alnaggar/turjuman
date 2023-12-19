<?php

namespace Alnaggar\Turjuman;

use Alnaggar\Turjuman\Exceptions\UnsupportedLocaleException;
use Alnaggar\Turjuman\Support\RouteResolver;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Support\Traits\Macroable;

/**
 * Localizer class provides URL localization capabilities for Laravel applications using Turjuman.
 * 
 * This class serves as the main entry point for handling URL localization in a Laravel application.
 * It facilitates the management of localized routes and URLs within a Laravel application.
 *
 * @package Alnaggar\Turjuman
 */
class Localizer
{
    use Macroable;

    /**
     * The instance of the Laravel application.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The instance of the Laravel configuration repository.
     *
     * @var \Illuminate\Config\Repository
     */
    protected $config;

    /**
     * The instance of the Laravel router.
     *
     * @var \Illuminate\Routing\Router 
     */
    protected $router;

    /**
     * The instance of the Laravel cookie jar.
     *
     * @var \Illuminate\Cookie\CookieJar
     */
    protected $cookies;

    /**
     * The instance of the Laravel session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * Group Collection Instance.
     * 
     * @var \Illuminate\Support\Collection
     */
    protected $groups;

    /**
     * Array of group ignored routes added by ignore function.
     * 
     * @var array<\Illuminate\Routing\Route>
     */
    protected $groupIgnoredRoutes = [];

    /**
     * Current Group.
     * 
     * @var \Alnaggar\Turjuman\Group
     */
    protected $currentGroup;

    /**
     * Config Group Attributes.
     * 
     * @var \Alnaggar\Turjuman\GroupAttributes
     */
    protected $configAttributes;

    /**
     * Current Locale.
     * 
     * @var \Alnaggar\Turjuman\Locale
     */
    protected $currentLocale;

    /**
     * Creates a new Localizer instance.
     *
     * @param \Illuminate\Foundation\Application $app The instance of the Laravel application.
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->config = $app['config'];
        $this->router = $app['router'];
        $this->cookies = $app['cookie'];
        $this->session = $app['session'];

        $this->groups = collect();
    }

    /**
     * Define a new route group.
     *
     * @param \Closure $routes The closure containing the routes to be grouped.
     * @param array $attributes The attributes to apply to the group.
     * @return \Alnaggar\Turjuman\Group An instance of the Turjuman Group representing the route group.
     */
    public function group(\Closure $routes, array $attributes = []) : Group
    {
        // Store the current routes to later calculate the added routes within the group
        $routeCollection = $this->router->getRoutes();
        $this->groupIgnoredRoutes = $routeCollection->getRoutes();

        // Execute the closure to register routes within the group
        $routes();

        // Calculate the new routes added within the group
        $groupAttributes = new GroupAttributes($attributes, $this->getConfigAttributes());
        $groupRoutes = RouteResolver::getNewRoutes($this->groupIgnoredRoutes, $routeCollection->getRoutes());

        // Create and configure a new Turjuman Group instance
        return tap(new Group($this->app), fn (Group $group) =>
            $this->groups->add($group->setAttributes($groupAttributes)->setRoutes($groupRoutes))
        );
    }

    /**
     * Ignore routes within the current group.
     *
     * @param \Illuminate\Routing\Route|\Closure $routes The closure or route to be ignored.
     * @return void
     */
    public function ignore(Route|\Closure $routes) : void
    {
        if ($routes instanceof \Closure) {
            // Store the current routes to calculate the added routes within the closure
            $routeCollection = $this->router->getRoutes();
            $nonIgnoredRoutes = $routeCollection->getRoutes();

            // Execute the closure to register routes to be ignored
            $routes();

            // Calculate the new routes added within the closure and update the ignored routes
            $this->groupIgnoredRoutes = array_merge(
                $this->groupIgnoredRoutes,
                RouteResolver::getNewRoutes($nonIgnoredRoutes, $routeCollection->getRoutes())
            );
        } else {
            // Add a single route to the ignored routes
            $this->groupIgnoredRoutes[] = $routes;
        }
    }

    /**
     * Add additional locales to the configuration.
     *
     * @param array<\Alnaggar\Turjuman\Locale> $locales The locales to be added to the configuration.
     * @return static
     */
    public function addConfigLocales(array $locales) : static
    {
        return $this->setConfigLocales(array_merge($this->getConfigAttributes()->getSupportedLocales(), $locales));
    }

    /**
     * Set the supported locales in the configuration.
     *
     * @param array<\Alnaggar\Turjuman\Locale> $locales The locales to be set in the configuration.
     * @return static
     */
    public function setConfigLocales(array $locales) : static
    {
        return $this->setConfigAttributes(new GroupAttributes(['supported_locales' => $locales] + $this->config->get('turjuman')));
    }

    /**
     * Set the default locale in the configuration.
     *
     * @param string $locale The default locale to be set in the configuration.
     * @return static
     */
    public function setConfigDefaultLocale(string $locale) : static
    {
        return $this->setConfigAttributes(new GroupAttributes(['default_locale' => $locale] + $this->config->get('turjuman')));
    }

    /**
     * Set the configuration attributes.
     *
     * @param \Alnaggar\Turjuman\GroupAttributes $attributes The attributes to be set as the configuration attributes.
     * @return static
     */
    public function setConfigAttributes(GroupAttributes $attributes) : static
    {
        $this->config->set('turjuman', $attributes->getAllAttributes());
        $this->configAttributes = $attributes;

        return $this;
    }

    /**
     * Set the current locale.
     *
     * @param string $locale The locale code to set as the current locale.
     * @return \Alnaggar\Turjuman\Locale The Locale instance for the current locale.
     * @throws \Alnaggar\Turjuman\Exceptions\UnsupportedLocaleException If the provided locale is not supported.
     */
    public function setCurrentLocale(string $locale) : Locale
    {
        // Retrieve the Locale instance for the provided locale code
        $currentLocale = $this->getLocale($locale);

        // Throw an exception if the provided locale is not supported
        if (is_null($currentLocale)) {
            throw UnsupportedLocaleException::unsupportedCurrentLocale($locale);
        }

        // Set the current locale in the application
        $this->app->setLocale($locale);
        $this->app->setFallbackLocale($this->getDefaultLocale()->getCode());

        // Store the current locale in the session and as a cookie
        $this->session->put($this->getCurrentAttributes()->getLocaleIdentifier(), $locale);
        $this->cookies->queue($this->cookies->forever($this->getCurrentAttributes()->getLocaleIdentifier(), $locale));

        // Set the locale for LC_TIME and LC_MONETARY
        setlocale(LC_TIME, $currentLocale->getRegional());
        setlocale(LC_MONETARY, $currentLocale->getRegional());

        // Set the current locale property and return the Locale instance
        return $this->currentLocale = $currentLocale;
    }

    /**
     * Get the configuration attributes.
     *
     * @return \Alnaggar\Turjuman\GroupAttributes The GroupAttributes instance for the configuration attributes.
     */
    public function getConfigAttributes() : GroupAttributes
    {
        if (is_null($this->configAttributes)) {
            $this->setConfigAttributes(new GroupAttributes($this->config->get('turjuman')));
        }

        return $this->configAttributes;
    }

    /**
     * Get the attributes for the current group.
     *
     * @return \Alnaggar\Turjuman\GroupAttributes The GroupAttributes instance for the current group's attributes.
     */
    public function getCurrentAttributes() : GroupAttributes
    {
        return $this->getCurrentGroup()->getAttributes();
    }

    /**
     * Get the current group based on the router's current route.
     *
     * @return \Alnaggar\Turjuman\Group|null The current Group instance or null if no matching group is found.
     */
    public function getCurrentGroup() : ?Group
    {
        return $this->currentGroup ??=
            $this->groups->first(fn (Group $group) => $group->isLocalizedRoute($this->router->getCurrentRoute()));
    }

    /**
     * Get the current locale.
     *
     * @return \Alnaggar\Turjuman\Locale|null The current Locale instance or null if not set.
     */
    public function getCurrentLocale() : ?Locale
    {
        return $this->currentLocale;
    }

    /**
     * Get a specific locale by its code.
     *
     * @param string $locale The code of the desired locale.
     * @return \Alnaggar\Turjuman\Locale|null The Locale instance for the specified code or null if not found.
     */
    public function getLocale(string $locale) : ?Locale
    {
        return $this->getSupportedLocales()[$locale] ?? null;
    }

    /**
     * Get the current group's supported locales.
     * 
     * @return array<string, \Alnaggar\Turjuman\Locale>
     */
    public function getSupportedLocales() : array
    {
        return $this->getCurrentAttributes()->getSupportedLocales();
    }

    /**
     * Get the current group's default locale.
     * 
     * @return \Alnaggar\Turjuman\Locale
     */
    public function getDefaultLocale() : Locale
    {
        return $this->getCurrentAttributes()->getDefaultLocale();
    }

    /**
     * Return an associative array of code/property based on the current group's supported locales.
     * 
     * @param string $property The property to retrieve.
     * @param mixed $default A value to be returned if property not exists on a certian locale instance.
     * If a clouser is passed, the locale instance will be provided as a parameter. 
     * 
     * @return array<string, mixed> An associative array with locale codes as keys and corresponding property values.
     */
    public function getLocalesByProperty(string $property, mixed $default = null) : array
    {
        return $this->getCurrentAttributes()->getLocalesByProperty($property, $default);
    }

    /**
     * Get the localized URL for the provided URL and locale.
     *
     * @param string $url The URL to localize.
     * @param string|null $locale The locale code to use for localization. Defaults to the current locale.
     * @return string|null The localized URL (decoded), or null if the URL is not localized in any registered group.
     */
    public function getLocalizedUrl(string $url, ?string $locale = null) : ?string
    {
        /** @var \Alnaggar\Turjuman\Group $group */

        $route = RouteResolver::getRouteByUrl($url, 'GET');
        $locale ??= $this->getCurrentLocale()?->getCode();

        if ($route && $locale) {
            if ($group = $this->groups->first(fn (Group $group) => $group->isLocalizedRoute($route))) {
                if (array_key_exists($locale, $group->getAttributes()->getSupportedLocales())) {
                    return $group->getLocalizedUrl($url, $locale);
                }
            }
        }

        return null;
    }

    /**
     * Get the non-localized URL for the provided URL.
     *
     * @param string $url The URL to process.
     * @return string|null The non-localized URL (decoded), or null if the URL is not associated with any registered route.
     */
    public function getNonLocalizedUrl(string $url) : ?string
    {
        if ($route = RouteResolver::getRouteByUrl($url, 'GET')) {
            return $this->groups->first(fn (Group $group) => $group->isLocalizedRoute($route))?->getNonLocalizedUrl($url) ?? $url;
        }

        return null;
    }

    /**
     * Get the localized page path for the provided path and locale.
     * It works for Laravel views and Inertia pages.
     * 
     * @param string $path The path to localize.
     * @param string|null $locale The locale code to use for localization. Defaults to the current locale.
     * @return string The localized page path.
     */
    public function getLocalizedPagePath(string $path, ?string $locale = null) : string
    {
        $prefix = ($locale ?? $this->getCurrentLocale()->getCode()) . '/';

        return str_replace('.', '/', $prefix . $path);
    }

    /**
     * Check if the given URL is localized in any of the registered groups.
     *
     * @param string $url The URL to check.
     * @param string $method The HTTP method for the URL. Defaults to 'GET'.
     * @return bool Whether the URL is localized in any group.
     */
    public function isLocalizedUrl(string $url, string $method = 'GET') : bool
    {
        return $this->groups->contains(fn (Group $group) => $group->isLocalizedUrl($url, $method));
    }

    /**
     * Check if the provided route is localized within any of the registered groups.
     *
     * @param \Illuminate\Routing\Route|string|null $route The route to check. Defaults to the current route.
     * @return bool Whether the route is localized within any group.
     */
    public function isLocalizedRoute(Route|string|null $route = null) : bool
    {
        return $this->groups->contains(fn (Group $group) => $group->isLocalizedRoute($route ?? $this->router->getCurrentRoute()));
    }

    /**
     * Check if the provided locale is supported within the current group.
     *
     * @param string $locale The locale code to check.
     * @return bool Whether the locale is supported within the current group.
     */
    public function isSupportedLocale(string $locale) : bool
    {
        return (bool) $this->getLocale($locale);
    }

    /**
     * Check if the provided locale is the current locale.
     *
     * @param string $locale The locale code to check.
     * @return bool Whether the provided locale is the current locale.
     */
    public function isCurrentLocale(string $locale) : bool
    {
        return $this->getCurrentLocale()->getCode() === $locale;
    }

    /**
     * Check if the provided locale is the default locale.
     *
     * @param string $locale The locale code to check.
     * @return bool Whether the provided locale is the default locale.
     */
    public function isDefaultLocale(string $locale) : bool
    {
        return $this->getDefaultLocale()->getCode() === $locale;
    }
}