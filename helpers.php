<?php

if (! function_exists('turjuman')) {
    /**
     * Localize the passed URL or return the Turjuman instance.
     *
     * @param string|null $url
     * @param string|null $locale
     * @return \Alnaggar\Turjuman\Localizer|string|null
     */
    function turjuman(?string $url = null, ?string $locale = null)
    {
        /** @var \Alnaggar\Turjuman\Localizer */
        $localizer = app('turjuman');

        return is_null($url) ? $localizer : $localizer->getLocalizedUrl($url, $locale);
    }
}
