<?php

namespace Alnaggar\Turjuman;

use Alnaggar\Turjuman\Support\RouteResolver;
use Alnaggar\Turjuman\Support\UrlProcessor;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;

/**
 * Group class represents a grouping of routes with localization capabilities.
 *
 * This class is responsible for managing a group of routes and handling localization features.
 * It allows the setting of group attributes, associating routes with the group, and generating
 * localized URLs based on the specified locale and group configuration. The class also provides
 * methods to determine if a route is localized within the group and to create localized routes
 * based on a source route.
 *
 * @package Alnaggar\Turjuman
 */
class Group
{
    /**
     * The instance of the Laravel router.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The instance of the Laravel URL generator.
     *
     * @var \Illuminate\Routing\UrlGenerator
     */
    protected $urlGenerator;

    /**
     * The group attributes that define the behavior and configuration of the group.
     *
     * @var \Alnaggar\Turjuman\GroupAttributes
     */
    protected $attributes;

    /**
     * The array of routes associated with the group.
     *
     * @var array<\Illuminate\Routing\Route>
     */
    protected $routes;

    /**
     * A map that links non-localized routes to their localized forms.
     *
     * @var array<string, array<string, \Illuminate\Routing\Route>>
     */
    protected $map;

    /**
     * A reverse map that links localized routes to their non-localized forms.
     *
     * @var array<string, \Illuminate\Routing\Route>
     */
    protected $reverseMap;

    /**
     * Creates a new Group instance.
     *
     * @param \Illuminate\Foundation\Application $app The instance of the Laravel application.
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->router = $app['router'];
        $this->urlGenerator = $app['url'];
    }

    /**
     * Sets the group attributes.
     * 
     * @param \Alnaggar\Turjuman\GroupAttributes $attributes
     * @return static
     */
    public function setAttributes(GroupAttributes $attributes) : static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Set the routes for the current group and initialize related mapping structures.
     *
     * This method accepts an array of Route instances, sets them as the routes for the current group.
     * It also triggers the creation of localized GET routes for qualifying routes based on group attributes.
     *
     * @param array<\Illuminate\Routing\Route> $routes The array of Route instances to set as group routes.
     * @return static The current instance for method chaining.
     */
    public function setRoutes(array $routes) : static
    {
        // Initialize routes, map, and reverseMap arrays to an empty state
        $this->routes = $this->map = $this->reverseMap = [];

        // Iterate through provided routes
        foreach ($routes as $route) {
            // Add the route to the routes array
            $this->routes[] = $route;

            // Check conditions for creating localized GET routes
            if (! $route->isFallback && in_array('GET', $route->methods())) {
                if ($this->attributes->isLocaleDisplayTypeSegment()) {
                    $this->createLocalizedGetRoutes($route);
                } else if ($this->attributes->isLocaleDisplayTypeHidden()) {
                    $this->createLocalizedGetRoutesWithHiddenLocale($route);
                }
            }
        }

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Returns the group attributes.
     * 
     * @return \Alnaggar\Turjuman\GroupAttributes
     */
    public function getAttributes() : GroupAttributes
    {
        return $this->attributes;
    }

    /**
     * Returns the group localized routes.
     * 
     * @return array<\Illuminate\Routing\Route>
     */
    public function getRoutes() : array
    {
        return $this->routes;
    }

    /**
     * Generate a localized URL based on the provided URL and locale.
     *
     * This function constructs a URL with the specified locale, considering display types and settings.
     * If the default locale is set to be hidden and matches the provided locale, a non-localized URL is returned.
     * Otherwise, the URL is generated based on the provided locale and display type.
     *
     * @param string $url The original URL.
     * @param string $locale The desired locale.
     * @return string The generated localized URL.
     */
    public function getLocalizedUrl(string $url, string $locale) : string
    {
        // Get the alias for the specified locale
        $alias = $this->attributes->getLocalesAliases()[$locale];

        // Check if the default locale is set to be hidden and matches the provided locale
        $shouldHideLocale = $this->attributes->isHideDefault() && $this->attributes->getDefaultLocale()->getCode() === $locale;

        // If the locale should be hidden, return a non-localized URL
        if ($shouldHideLocale) {
            return $this->getNonLocalizedUrl($url);
        }

        // Extract route, parameters, and queries from the provided URL
        $route = RouteResolver::getRouteByUrl($url, 'GET');
        [$parameters, $queries] = [
            UrlProcessor::extractParameters($route, $url),
            UrlProcessor::extractQueries($url)
        ];

        // Handle URL construction based on display type
        if ($this->attributes->isLocaleDisplayTypeSegment()) {
            $route = $this->reverseMap[$route->getDomain() . $route->uri()] ?? $route;
            $route = $this->map[$route->getDomain() . $route->uri()][$locale];
            Arr::forget($parameters, Localizer::LOCALE_IDENTIFIER);

            // Add locale identifier to parameters if it exists in route wheres
            if (array_key_exists(Localizer::LOCALE_IDENTIFIER, $route->wheres)) {
                $parameters = array_merge($parameters, [Localizer::LOCALE_IDENTIFIER => $alias]);
            }
        } elseif ($this->attributes->isLocaleDisplayTypeQuery()) {
            $queries = array_merge($queries, [$this->attributes->getDisplayLocation() => $alias]);
        } else {
            $route = $this->reverseMap[$route->getDomain() . $route->uri()] ?? $route;
            $route = $this->map[$route->getDomain() . $route->uri()][$locale] ?? $route;
        }

        // Construct the localized URL using the adjusted parameters and queries
        return urldecode($this->urlGenerator->toRoute($route, $parameters + $queries, true));
    }

    /**
     * Generate the non-localized URL for the given URL, handling localization attributes if applicable.
     *
     * @param string $url The URL for which to generate the non-localized version.
     * @return string The non-localized URL.
     */
    public function getNonLocalizedUrl(string $url) : string
    {
        // Extract route, parameters, and queries from the provided URL
        $route = RouteResolver::getRouteByUrl($url, 'GET');
        [$parameters, $queries] = [
            UrlProcessor::extractParameters($route, $url),
            UrlProcessor::extractQueries($url)
        ];

        // Check if the route is localized within the current group.
        // If the route is not localized within the group, return the URL without modifying its parameters or queries.
        if ($this->isLocalizedRoute($route)) {
            // Adjust parameters and queries based on the display type specified in group attributes
            if ($this->attributes->isLocaleDisplayTypeQuery()) {
                Arr::forget($queries, $this->attributes->getDisplayLocation());
            } else {
                $route = $this->reverseMap[$route->getDomain() . $route->uri()] ?? $route;

                if ($this->attributes->isLocaleDisplayTypeSegment()) {
                    Arr::forget($parameters, Localizer::LOCALE_IDENTIFIER);
                }
            }
        }

        // Construct the non-localized URL using the adjusted parameters and queries
        return urldecode($this->urlGenerator->toRoute($route, $parameters + $queries, true));
    }

    /**
     * Determine if the provided URL, using the specified HTTP method, corresponds to a localized route within the current group.
     * 
     * @param string $url The URL to check for localization.
     * @param string $method The HTTP method used for the URL request.
     * @return bool True if the URL corresponds to a localized route within the current group, otherwise false.
     */
    public function isLocalizedUrl(string $url, string $method) : bool
    {
        // Attempt to obtain the Route instance based on the provided URL and HTTP method
        if ($route = RouteResolver::getRouteByUrl($url, $method)) {
            // Check if the resolved Route is localized within the current group
            return $this->isLocalizedRoute($route);
        }

        // Return false if no matching Route is found for the provided URL and method
        return false;
    }

    /**
     * Determine if the given route is a localized route within the current group.
     *
     * @param string|\Illuminate\Routing\Route $route The route name or instance to be checked for localization.
     * @return bool True if the route is localized within the current group, otherwise false.
     */
    public function isLocalizedRoute(string|Route $route) : bool
    {
        // If the input is a string, resolve it to a Route instance using the RouteResolver
        if (is_string($route)) {
            $route = RouteResolver::getRouteByName($route);
        }

        // Check if the resolved or provided Route instance is present in the group's localized routes
        return in_array($route, $this->routes, true);
    }

    /**
     * Create localized routes based on the provided source route.
     *
     * This function generates localized routes for each supported locale based on the provided source route.
     * It considers locale aliases, display locations, and whether the source route is named.
     * The generated routes are added to the lookup maps for further reference.
     *
     * @param \Illuminate\Routing\Route $source The source route for which localized routes are generated.
     * @return void
     */
    protected function createLocalizedGetRoutes(Route $source) : void
    {
        // Retrieve locale aliases, route aliases, and display location from group attributes
        $localesAliases = $this->attributes->getLocalesAliases();
        $routesAliases = $this->attributes->getRoutesAliases();
        $segmentIndex = $this->attributes->getDisplayLocation();

        // Get the URI of the source route and check if it is named
        $sourceUri = $source->uri();
        $isNamed = ! is_null($source->getName());

        // Initialize an array to store locales that do not have an alias for this route.
        $locales = [];

        // Iterate through supported locales and generate localized routes
        foreach ($localesAliases as $code => $alias) {
            // Get the localized URI based on route aliases
            $localizedUri = $routesAliases[$code][$source->getDomain() . $sourceUri] ?? $sourceUri;

            // Check if the source route is named or if the localized URI differs
            // If either condition is met, create a custom route for this locale
            if ($isNamed || $localizedUri !== $sourceUri) {
                // Add the locale alias to the localized URI at the specified segment index
                $localizedUri = UrlProcessor::addSegmentToPath($localizedUri, $alias, $segmentIndex);

                // Create the localized route using the modified URI
                $localizedRoute = $this->createLocalizedGetRoute($source, $localizedUri);

                // Conditionally set the route name if it is named
                $localizedRoute = $isNamed ? $localizedRoute->name(".$code") : $localizedRoute;

                // Add the localized route to the lookup maps
                $this->addLookups($source, $localizedRoute, [$code]);
            } else {
                // If the source route is not named, and the localized URI is the same as the source URI,
                // store the locale in the $locales array for further processing
                $locales[$code] = $alias;
            }
        }

        // If there are locales that do not have an alias for this route,
        // create a route with a placeholder segment for them.
        if ($locales) {
            // Add a placeholder segment for the locale to the source URI
            $localizedUri = UrlProcessor::addSegmentToPath($sourceUri, '{' . Localizer::LOCALE_IDENTIFIER . '}', $segmentIndex);

            // Create a localized route with the placeholder segment and restrict to specified locales
            $localizedRoute = $this->createLocalizedGetRoute($source, $localizedUri)->whereIn(Localizer::LOCALE_IDENTIFIER, $locales);

            // Add the localized route to the lookup maps
            $this->addLookups($source, $localizedRoute, array_keys($locales));
        }
    }

    /**
     * Creates localized GET routes with hidden locale for the given source route.
     * Retrieves supported locales and route aliases from group attributes,
     * then iterates through supported locales to generate localized routes
     * based on route aliases. If the localized URI differs from the source,
     * a custom route is created for that locale.
     * The generated routes are added to the lookup maps for further reference.
     * 
     * @param \Illuminate\Routing\Route $source The source route for which localized routes are created.
     * @return void
     */
    protected function createLocalizedGetRoutesWithHiddenLocale(Route $source) : void
    {
        // Retrieve supported locales and route aliases from group attributes
        $supportedLocales = array_keys($this->attributes->getSupportedLocales());
        $routesAliases = $this->attributes->getRoutesAliases();

        // Get the URI of the source route and check if it is named
        $sourceUri = $source->uri();
        $isNamed = ! is_null($source->getName());

        // Iterate through supported locales and generate localized routes
        foreach ($supportedLocales as $code) {
            // Get the localized URI based on route aliases
            $localizedUri = $routesAliases[$code][$source->getDomain() . $sourceUri] ?? $sourceUri;

            // Check if the localized URI differs; if so, create a custom route for this locale
            if ($localizedUri !== $sourceUri) {
                // Create the localized route using the modified URI
                $localizedRoute = $this->createLocalizedGetRoute($source, $localizedUri);

                // Conditionally set the route name if it is named
                $localizedRoute = $isNamed ? $localizedRoute->name(".$code") : $localizedRoute;

                // Add the localized route to the lookup maps
                $this->addLookups($source, $localizedRoute, [$code]);
            }
        }
    }

    /* Creates a localized GET route based on the source route and the localized URI.
     *
     * @param \Illuminate\Routing\Route $source
     * @param string $localizedUri
     * @return \Illuminate\Routing\Route
     */
    protected function createLocalizedGetRoute(Route $source, string $localizedUri) : Route
    {
        $route = $this->router->newRoute('GET', $localizedUri, null)
            ->setAction($source->getAction())
            ->setDefaults($source->defaults)
            ->setWheres($source->wheres)
            ->withTrashed($source->allowsTrashedBindings())
            ->block($source->locksFor(), $source->waitsFor());

        return $this->router->getRoutes()->add($route);
    }

    /**
     * Add lookups for a non-localized route and its corresponding localized route.
     *
     * @param \Illuminate\Routing\Route $nonLocalizedRoute
     * @param \Illuminate\Routing\Route $localizedRoute
     * @param array $locales
     * @return void
     */
    protected function addLookups(Route $nonLocalizedRoute, Route $localizedRoute, array $locales) : void
    {
        $this->routes[] = $localizedRoute;

        $nonLocalizedKey = $nonLocalizedRoute->getDomain() . $nonLocalizedRoute->uri();
        $localizedKey = $localizedRoute->getDomain() . $localizedRoute->uri();

        foreach ($locales as $locale) {
            $this->map[$nonLocalizedKey][$locale] = $localizedRoute;
        }
        $this->reverseMap[$localizedKey] = $nonLocalizedRoute;
    }
}