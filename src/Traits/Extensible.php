<?php

namespace Alnaggar\Turjuman\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

/**
 * Extensible trait provides extensible behavior for classes implementing ArrayAccess.
 *
 * This trait allows objects to be extended in terms of ArrayAccess, enabling changes
 * to the underlying data. It provides a container for properties in the form of a property
 * bag, accessible through ArrayAccess methods in a case-sensitive manner.
 *
 * Features:
 * - Property bags for key-value pairs representing object properties.
 * - Definition of immutable properties, restricting modifications to specific properties.
 * - Implementation of ArrayAccess methods for property access and manipulation.
 * - Magic methods (__isset, __get, __set) for additional property handling.
 * - Dynamic method calls through __call for case-insensitive get method invocation.
 * - Utilizes the Macroable trait for dynamic addition of macros.
 *
 * @package Alnaggar\Turjuman\Traits
 */
trait Extensible
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * Container for properties in the Extensible trait.
     *
     * This associative array stores key-value pairs representing properties
     * for the class implementing the Extensible trait.
     *
     * @var array<string, mixed>
     */
    protected $propertyBag = [];

    /**
     * Array containing the names of properties that are considered immutable.
     *
     * This array stores the names of properties for which modifications are restricted.
     * When attempting to set or unset values for properties listed in this array,
     * a \LogicException will be thrown to indicate that the property is immutable.
     *
     * @var array<string>
     */
    protected $immutableBag = [];

    /**
     * Check if the specified offset exists.
     *
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset) : bool
    {
        return array_key_exists($offset, $this->propertyBag);
    }

    /**
     * Retrieve the value at the specified offset.
     *
     * This method attempts to map properties using get methods in a case-insensitive manner.
     * If a corresponding get method exists for the specified offset, it is called to retrieve the value.
     * If no get method is found, the property is directly retrieved from the underlying array,
     * and null is returned if the property does not exist.
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset) : mixed
    {
        $method = 'get' . Str::studly($offset);

        // Check if a custom get method exists
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        // Return the property directly from the array, or null if not found
        return $this->propertyBag[$offset] ?? null;
    }


    /**
     * Set the value at the specified offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws \LogicException
     * @return void
     */
    public function offsetSet($offset, $value) : void
    {
        $this->throwIfImmutable($offset);

        $this->propertyBag[$offset] = $value;
    }

    /**
     * Unset the value at the specified offset.
     *
     * @param mixed $offset
     * @throws \LogicException
     * @return void
     */
    public function offsetUnset($offset) : void
    {
        $this->throwIfImmutable($offset);

        unset($this->propertyBag[$offset]);
    }

    /**
     * Check if a property is set using the __isset method.
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name) : bool
    {
        return $this->offsetExists(Str::snake($name));
    }

    /**
     * Retrieve the value of a property using the __get method.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) : mixed
    {
        return $this->offsetGet(Str::snake($name));
    }

    /**
     * Set the value of a property using the __set method.
     *
     * @param string $name
     * @param mixed $value
     * @throws \LogicException
     * @return void
     */
    public function __set(string $name, mixed $value) : void
    {
        $this->offsetSet($name, $value);
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters) : mixed
    {
        if (static::hasMacro($method)) {
            return static::macroCall($method, $parameters);
        }

        return $this->__get(Str::replaceFirst('get', '', $method));
    }

    /**
     * Check if a property is immutable and throw an exception if it is.
     *
     * @param string $property
     * @throws \LogicException
     * @return void
     */
    protected function throwIfImmutable(string $property) : void
    {
        if ($this->isImmutable($property)) {
            throw new \LogicException(sprintf('%s %s property is immutable.', static::class, $property));
        }
    }

    /**
     * Checks if a given property is marked as immutable.
     * 
     * @param string $property
     * @return bool
     */
    protected function isImmutable(string $property) : bool
    {
        return in_array($property, $this->immutableBag);
    }
}
