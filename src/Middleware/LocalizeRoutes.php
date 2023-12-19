<?php

namespace Alnaggar\Turjuman\Middleware;

use Closure;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LocalizeRoutes
 *
 * This middleware checks if the request is a GET request and if current route is localized. It ensures that the URL matches the localized URL, and if not, it redirects to the correct URL.
 * Additionally, it removes the locale parameter from the route to avoid affecting route parameter binding when URLs match.
 *
 * @package Alnaggar\Turjuman
 */
class LocalizeRoutes
{
    /**
     * The instance of the Turjuman localizer.
     * 
     * @var \Alnaggar\Turjuman\Localizer
     */
    protected $localizer;

    /**
     * The instance of the Laravel session manager.
     *
     * @var \Illuminate\Session\SessionManager
     */
    protected $session;

    /**
     * The instance of the Laravel Redirector.
     * 
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * Creates a new LocalizeRoutes instance.
     * 
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->localizer = $app['turjuman'];
        $this->session = $app['session'];
        $this->redirector = $app['redirect'];
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next) : Response
    {
        if ($request->isMethod('GET') && $this->localizer->getCurrentLocale()) {
            if (! $this->localizer->getCurrentAttributes()->isLocaleDisplayTypeQuery()) {
                $requestUrl = rawurldecode($request->fullUrl());
                $requestUrlWithoutQueryString = rtrim(preg_replace('/\?.*/', '', $requestUrl), '/');

                $localizedUrl = $this->localizer->getLocalizedUrl($requestUrl);
                $localizedUrlWithoutQueryString = rtrim(preg_replace('/\?.*/', '', $localizedUrl), '/');

                if ($requestUrlWithoutQueryString !== $localizedUrlWithoutQueryString) {
                    $this->session->reflash();

                    return $this->redirector->to($localizedUrl);
                } elseif ($this->localizer->getCurrentAttributes()->isLocaleDisplayTypeSegment()) {
                    // If the URLs match and the display type is set to display the locale as a segment in the URl,
                    // remove the locale parameter from the route, so it does not affect route parameter binding
                    $request->route()->forgetParameter($this->localizer->getCurrentAttributes()->getLocaleIdentifier());
                }
            }
        }

        // Continue with the next middleware in the pipeline
        return $next($request);
    }
}
