<?php

namespace Alnaggar\Turjuman\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SetLocale
 *
 * This middleware is responsible for determining the current locale and setting it in the Turjuman localizer class for localized routes when the current locale is not already set by a service provider or a previous middleware. It checks different sources such as request input, URL, session, cookies, and user preferences to determine the current locale.
 *
 * @package Alnaggar\Turjuman
 */
class SetLocale
{
    /**
     * The instance of the Turjuman localizer.
     * 
     * @var \Alnaggar\Turjuman\Localizer
     */
    protected $localizer;

    /**
     * Creates a new LocalizeRoutes instance.
     * 
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->localizer = $app['turjuman'];
    }

    /**
     * Handle an incoming request
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next) : Response
    {
        if ($this->localizer->isLocalizedRoute() && is_null($this->localizer->getCurrentLocale())) {
            $currentLocale = $this->determineCurrentLocale($request);

            $request->setRequestLocale($currentLocale);
            $this->localizer->setCurrentLocale($currentLocale);
        }

        // Continue with the next middleware in the pipeline
        return $next($request);
    }

    /**
     * Determine the current locale by checking various sources in a predefined order.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function determineCurrentLocale(Request $request) : string
    {
        $currentLocale = null;

        // Get locale aliases for quick reference
        $localeAliases = array_flip($this->localizer->getCurrentAttributes()->getLocaleAliases());

        // Get locale identifier for quick reference
        $localeIdentifier = $this->localizer->getCurrentAttributes()->getLocaleIdentifier();

        // Counter for iteration
        $i = 0;

        // Iterate through potential sources to determine the current locale
        while (is_null($currentLocale) || ! $this->localizer->isSupportedLocale($currentLocale)) {
            $currentLocale = match (++$i) {
                1 => $this->extractLocaleFromNonGetRequestInput($request),
                2 => $this->extractLocaleFromUrl($request),
                3 => $request->session()->get($localeIdentifier),
                4 => $request->cookie($localeIdentifier),
                5 => $this->fetchUserLocale(),
                6 => $request->getPreferredLanguage($localeAliases),
                default => $this->localizer->getDefaultLocale()->getCode()
            };

            // Map the current locale if found using locale aliases, if available
            if (! is_null($currentLocale)) {
                $currentLocale = $localeAliases[$currentLocale] ?? $currentLocale;
            }
        }

        return $currentLocale;
    }

    /**
     * Retrieve the locale from non-GET request input based on the display type.
     * 
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function extractLocaleFromNonGetRequestInput(Request $request) : ?string
    {
        if (! $request->isMethod('GET')) {
            if ($this->localizer->getCurrentAttributes()->isLocaleDisplayTypeHidden()) {
                return (string) $request->input($this->localizer->getCurrentAttributes()->getLocaleIdentifier());
            }
        }

        return null;
    }

    /**
     * Retrieve the locale from the URL based on the display type.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function extractLocaleFromUrl(Request $request) : ?string
    {
        if ($request->isMethod('GET')) {
            $displayLocation = $this->localizer->getCurrentAttributes()->getDisplayLocation();

            if ($this->localizer->getCurrentAttributes()->isLocaleDisplayTypeSegment()) {
                return $request->segment(min($displayLocation, count($request->segments())));
            } elseif ($this->localizer->getCurrentAttributes()->isLocaleDisplayTypeQuery()) {
                return $request->query($displayLocation);
            }
        }

        return null;
    }

    /**
     * Obtain the user's locale from a custom source, such as a database.
     *
     * @return string|null
     */
    protected function fetchUserLocale() : ?string
    {
        return null;
    }
}
