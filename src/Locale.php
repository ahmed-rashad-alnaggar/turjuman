<?php

namespace Alnaggar\Turjuman;

use Alnaggar\Turjuman\Traits\Extensible;

/**
 * Locale class represents a language locale in Turjuman.
 *
 * This class encapsulates the properties of a language locale, providing a normalized
 * and extensible way to access and manage these properties by utilizing the Extensible trait.
 *
 * @property string $code
 * @property string $name
 * @property string $native
 * @property string $script
 * @property array $regional
 * @property string $direction
 * @property string|null $alias
 * 
 * @package Alnaggar\Turjuman
 */
class Locale implements \ArrayAccess
{
    use Extensible;

    /**
     * Creates a new Locale instance.
     * 
     * @param array<string, mixed> $properties Associative array of property/value pairs.
     * The array must include essential properties which are [code, name, native, script].
     * Any additional entries will be treated as extra properties like [direction, native_speakers],
     * accessible via the Extensible trait access methods.
     * 
     * @return void
     */
    public function __construct(array $properties)
    {
        $this->propertyBag = $properties;

        $this->immutableBag = ['code', 'alias'];
    }

    /**
     * Returns locale code.
     * 
     * @return string
     */
    public function getCode() : string
    {
        return $this->propertyBag['code'];
    }

    /**
     * Returns locale name.
     * 
     * @return string
     */
    public function getName() : string
    {
        return $this->propertyBag['name'];
    }

    /**
     * Returns locale native.
     * 
     * @return string
     */
    public function getNative() : string
    {
        return $this->propertyBag['native'];
    }

    /**
     * Returns locale script.
     * 
     * @return string
     */
    public function getScript() : string
    {
        return $this->propertyBag['script'];
    }

    /**
     * Returns the locale regional.
     * 
     * @return array<string>
     */
    public function getRegional() : array
    {
        if ($this->hasProperty('regional')) {
            return array_merge((array) $this->propertyBag['regional'], ['C', 'POSIX']);
        }

        // If the regional property is not defined, expect the regional from the locale code and add fallbacks.
        $snakeCode = preg_replace('/-+/u', '_', $this->getCode());
        $kebabCode = preg_replace('/_+/u', '-', $this->getCode());

        return $this['regional'] = [
            "$snakeCode.utf8",
            "$snakeCode.UTF-8",
            "$kebabCode.utf8",
            "$kebabCode.UTF-8",
            $snakeCode,
            $kebabCode,
            'C',
            'POSIX'
        ];
    }

    /**
     * Returns the locale writing direction.
     * 
     * @return string
     */
    public function getDirection() : string
    {
        if ($this->hasProperty('direction')) {
            return $this->propertyBag['direction'];
        }

        // If the direction property is not defined, expect the direction from the locale script.
        return $this['direction'] = match ($this->getScript()) {
            // Other (historic) RTL scripts exist, but this list contains the only ones in current use.
            'Arab', 'Mong', 'Tfng', 'Thaa' => 'rtl',
            default => 'ltr'
        };
    }

    /**
     * Returns locale alias.
     * 
     * @return string|null
     */
    public function getAlias() : ?string
    {
        return $this->propertyBag['alias'];
    }

    /**
     * Returns all locale properties.
     * 
     * @return array<string, mixed>
     */
    public function getAllProperties() : array
    {
        return $this->propertyBag + ['regional' => $this->getRegional(), 'direction' => $this->getDirection()];
    }

    /**
     * Checks whether property exists or not.
     * 
     * @param string $property
     * @return bool
     */
    public function hasProperty(string $property) : bool
    {
        return $this->offsetExists($property);
    }
}