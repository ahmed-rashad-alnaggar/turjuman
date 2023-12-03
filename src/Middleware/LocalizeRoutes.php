<?php

namespace Alnaggar\Turjuman\Middleware;

use Alnaggar\Turjuman\Localizer;
use Alnaggar\Turjuman\Turjuman;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LocalizeRoutes
 *
 * Middleware for handling localization of routes based on the current locale settings.
 * This middleware checks if the request is a GET request and if current route is localized.
 * It ensures that the URL matches the localized URL, and if not, it redirects to the correct URL.
 * Additionally, it removes the locale parameter from the route to avoid affecting route
 * parameter binding when URLs match.
 *
 * @package Alnaggar\Turjuman\Middleware
 */
class LocalizeRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next) : Response
    {
        // Check if it's a GET request and this is a localized request
        if ($request->isMethod('GET') && Turjuman::getCurrentLocale()) {
            // Check if the display type is not set to display the locale as a query parameter in the URL
            if (! Turjuman::getCurrentAttributes()->isLocaleDisplayTypeQuery()) {
                $localizedUrl = Turjuman::getLocalizedUrl($request->fullUrl());

                // Check if the requested URL is different from the localized URL
                if (! $request->is(trim(parse_url($localizedUrl, PHP_URL_PATH), '/') ?: '/')) {
                    Session::reflash();

                    return Redirect::to($localizedUrl);
                } elseif (Turjuman::getCurrentAttributes()->isLocaleDisplayTypeSegment()) {
                    // If the URLs match and the display type is set to display the locale as a segment in the URl,
                    // remove the locale parameter from the route, so it does not affect route parameter binding
                    $request->route()->forgetParameter(Localizer::LOCALE_IDENTIFIER);
                }
            }
        }

        // Continue with the next middleware in the pipeline
        return $next($request);
    }
}
