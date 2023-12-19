<?php

namespace Alnaggar\Turjuman\Exceptions;

/**
 * Class UnsupportedLocaleException
 *
 * This class extends \DomainException to provide a specific type of exception for scenarios related to unsupported locales within the Turjuman package.
 *
 * @package Alnaggar\Turjuman
 */
class UnsupportedLocaleException extends \DomainException
{
    /**
     * Error code indicating an unsupported locale in the 'supported_locales' group attribute.
     */
    public const UNSUPPORTED_GROUP_LOCALE = 0;

    /**
     * Error code indicating an unsupported locale in the 'default_locale' group attribute.
     */
    public const UNSUPPORTED_DEFAULT_LOCALE = 1;

    /**
     * Error code indicating an unsupported locale when setting it as the current locale.
     */
    public const UNSUPPORTED_CURRENT_LOCALE = 2;

    /**
     * Creates an exception for an unsupported locale in the 'supported_locales' group attribute.
     *
     * @param string $code The unsupported locale code.
     * @return self
     */
    public static function unsupportedGroupLocale(string $code) : self
    {
        $message = "The locale '$code' is not supported (not configured in the supported_locales config attribute).";
        return new self($message, self::UNSUPPORTED_GROUP_LOCALE);
    }

    /**
     * Creates an exception for an unsupported default locale.
     *
     * @param string $code The unsupported default locale code.
     * @return self
     */
    public static function unsupportedDefaultLocale(string $code) : self
    {
        $message = "Specified default locale '$code' is not supported.";
        return new self($message, self::UNSUPPORTED_DEFAULT_LOCALE);
    }

    /**
     * Creates an exception for an unsupported current locale.
     *
     * @param string $code The unsupported current locale code.
     * @return self
     */
    public static function unsupportedCurrentLocale(string $code) : self
    {
        $message = "Trying to set current locale to '$code' which is not supported.";
        return new self($message, self::UNSUPPORTED_CURRENT_LOCALE);
    }
}
