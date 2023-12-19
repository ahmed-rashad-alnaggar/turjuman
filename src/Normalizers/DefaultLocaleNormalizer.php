<?php

namespace Alnaggar\Turjuman\Normalizers;

use Alnaggar\Turjuman\Contracts\GroupAttributeNormalizerInterface;
use Alnaggar\Turjuman\Exceptions\UnsupportedLocaleException;
use Alnaggar\Turjuman\GroupAttributes;
use Alnaggar\Turjuman\Locale;

/**
 * Class DefaultLocaleNormalizer
 *
 * This class implements the GroupAttributeNormalizerInterface and provides a method to normalize the default locale.
 * If the 'default_locale' attribute is missing in the provided attributes, it falls back to the default locale specified in the fallback attributes. The normalized locale is then validated against the 'supported_locales' attribute, and an exception is thrown if the default locale is not supported.
 *
 * @package Alnaggar\Turjuman
 */
class DefaultLocaleNormalizer implements GroupAttributeNormalizerInterface
{
    /**
     * Sets the default locale to the config value if it is missing.
     *
     * @param array<string, mixed> $attributes
     * @param \Alnaggar\Turjuman\GroupAttributes|null $fallbackAttributes
     *
     * @throws \Alnaggar\Turjuman\Exceptions\UnsupportedLocaleException
     *
     * @return \Alnaggar\Turjuman\Locale
     */
    public static function normalize(array $attributes, ?GroupAttributes $fallbackAttributes) : Locale
    {
        /** @var \Alnaggar\Turjuman\Locale|string */
        $locale = $attributes['default_locale'] ?? $fallbackAttributes->getDefaultLocale();
        $code = $locale instanceof Locale ? $locale->getCode() : $locale;

        if (! array_key_exists($code, $attributes['supported_locales'])) {
            throw UnsupportedLocaleException::unsupportedDefaultLocale($code);
        }

        return $attributes['supported_locales'][$code];
    }
}
