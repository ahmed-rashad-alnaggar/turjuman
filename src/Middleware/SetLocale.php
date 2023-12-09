<?php

namespace Alnaggar\Turjuman\Middleware;

use Alnaggar\Turjuman\Localizer;
use Alnaggar\Turjuman\Turjuman;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SetLocale
 *
 * Middleware for setting the current locale in the Turjuman package based on various sources.
 * This middleware is responsible for determining the current locale and setting it in the Turjuman package
 * for localized routes when the current locale is not already set. It checks different sources such as
 * request input, URL, session, cookies, and user preferences to determine the current locale.
 *
 * @package Alnaggar\Turjuman\Middleware
 */
class SetLocale
{
    /**
     * Handle an incoming request
     * 
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next) : Response
    {
        if (Turjuman::isLocalizedRoute() && is_null(Turjuman::getCurrentLocale())) {
            $currentLocale = $this->determineCurrentLocale($request);

            Turjuman::setCurrentLocale($currentLocale);
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
        // Counter for iteration
        $i = 0;

        // Get locale aliases for quick reference
        $localeAliases = array_flip(Turjuman::getCurrentAttributes()->getLocaleAliases());

        // Iterate through potential sources to determine the current locale
        while (! isset($currentLocale) || ! Turjuman::isSupportedLocale($currentLocale)) {
            $currentLocale = match (++$i) {
                1 => $this->extractLocaleFromNonGetRequestInput($request),
                2 => $this->extractLocaleFromUrl($request),
                3 => Session::get(Localizer::LOCALE_IDENTIFIER),
                4 => Cookie::get(Localizer::LOCALE_IDENTIFIER),
                5 => $this->fetchUserLocale(),
                6 => $request->getPreferredLanguage($localeAliases),
                default => Turjuman::getDefaultLocale()->getCode()
            };

            // Map the current locale using locale aliases if available
            $currentLocale = $localeAliases[$currentLocale] ?? $currentLocale;
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
            if (Turjuman::getCurrentAttributes()->isLocaleDisplayTypeHidden()) {
                return (string) $request->input(Localizer::LOCALE_IDENTIFIER);
            }
        }

        return null;
    }

    /**
     * Retrieve  the locale from the URL based on the display type.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function extractLocaleFromUrl(Request $request) : ?string
    {
        if ($request->isMethod('GET')) {
            $displayLocation = Turjuman::getCurrentAttributes()->getDisplayLocation();

            if (Turjuman::getCurrentAttributes()->isLocaleDisplayTypeSegment()) {
                return $request->segment(min($displayLocation, count($request->segments())));
            } elseif (Turjuman::getCurrentAttributes()->isLocaleDisplayTypeQuery()) {
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
        return null; // Implement your logic here if applicable
    }
}
