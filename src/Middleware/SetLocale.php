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
        $supportedLocales = array_keys(Turjuman::getSupportedLocales());

        // Try different sources to determine the current locale
        $currentLocale = $this->extractLocaleFromNonGetRequestInput($request)
            ?? $this->extractLocaleFromUrl($request)
            ?? Session::get(Localizer::LOCALE_IDENTIFIER)
            ?? Cookie::get(Localizer::LOCALE_IDENTIFIER)
            ?? $this->fetchUserLocale()
            ?? $request->getPreferredLanguage($supportedLocales)
            ?? Turjuman::getDefaultLocale()->getCode();

        // Obtain the locale code when an alias is utilized; otherwise, keep the original value.
        $currentLocale = array_flip(Turjuman::getCurrentAttributes()->getLocalesAliases())[$currentLocale] ?? $currentLocale;

        // Set the current locale to the default one if the determined locale is not supported due to reasons such as user manipulation in form input or URL query.
        if (! Turjuman::isSupportedLocale($currentLocale)) {
            $currentLocale = Turjuman::getDefaultLocale()->getCode();
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
