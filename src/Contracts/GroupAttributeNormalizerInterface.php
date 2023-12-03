<?php

namespace Alnaggar\Turjuman\Contracts;

use Alnaggar\Turjuman\GroupAttributes;

/**
 * Interface GroupAttributeNormalizerInterface
 *
 * Interface for normalizing group attributes in Turjuman.
 * This interface defines a method to normalize group attributes, allowing
 * for customization and handling of different attribute types and structures.
 *
 * @package Alnaggar\Turjuman\Contracts
 */
interface GroupAttributeNormalizerInterface
{
    /**
     * Normalize group attribute.
     *
     * @param array<string, mixed> $attributes The attributes to normalize.
     * @param \Alnaggar\Turjuman\GroupAttributes|null $fallbackAttributes Fallback attributes for normalization.
     * @return mixed The normalized attribute.
     */
    public static function normalize(array $attributes, ?GroupAttributes $fallbackAttributes) : mixed;
}
