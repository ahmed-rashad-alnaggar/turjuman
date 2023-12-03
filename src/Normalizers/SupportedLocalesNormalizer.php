<?php

namespace Alnaggar\Turjuman\Normalizers;

use Alnaggar\Turjuman\Contracts\GroupAttributeNormalizerInterface;
use Alnaggar\Turjuman\Exceptions\UnsupportedLocaleException;
use Alnaggar\Turjuman\GroupAttributes;
use Alnaggar\Turjuman\Locale;
use Illuminate\Support\Arr;

/**
 * Class SupportedLocalesNormalizer
 *
 * Supported locales normalizer responsible for normalizing the supported locales in the group attributes.
 * This class implements the GroupAttributeNormalizerInterface and provides a method to normalize 'supported_locales' in group attributes.
 * The normalization process involves validating and transforming the input array of supported locales into an associative array of Locale objects.
 * If 'supported_locales' is not provided in the input attributes, it falls back to the 'supported_locales' in the fallback attributes.
 *
 * @package Alnaggar\Turjuman\Normalizers
 */
class SupportedLocalesNormalizer implements GroupAttributeNormalizerInterface
{
    /**
     * Normalize the supported locales in the group attributes.
     *
     * @param array<string, mixed> $attributes Associative array of group attributes.
     * @param \Alnaggar\Turjuman\GroupAttributes|null $fallbackAttributes Fallback attributes for missing values.
     * @throws \Alnaggar\Turjuman\Exceptions\UnsupportedLocaleException If an unsupported locale is encountered.
     *
     * @return array An associative array of supported locales.
     */
    public static function normalize(array $attributes, ?GroupAttributes $fallbackAttributes) : array
    {
        if (isset($attributes['supported_locales'])) {
            $fallbackLocales = $fallbackAttributes?->getSupportedLocales();

            return Arr::mapWithKeys(
                $attributes['supported_locales'],
                function ($locale, $code) use ($fallbackLocales) {
                    // Locale can be in 3 cases:
                    // 1. string: here we will get the locale from the fallback locales
                    // 2. array: here we will create a new locale with the specified properties
                    // 3. Locale: here we will use it as it is.
    
                    /** @var Locale */
                    $locale = match (true) {
                        is_string($locale) => $fallbackLocales[$locale] ?? $locale,
                        is_array($locale) => new Locale($locale + ['code' => $code]),
                        default => $locale,
                    };

                    if (! $locale instanceof Locale) {
                        throw UnsupportedLocaleException::unsupportedGroupLocale($locale);
                    }

                    return [$locale->getCode() => $locale];
                }
            );
        }

        return $fallbackAttributes->getSupportedLocales();
    }
}
