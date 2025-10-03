<?php

namespace Formotron;

/**
 * Interface for key mappers.
 *
 * Key mappers are set on the data object class via the @see
 * Formotron\Attribute\MapKeys attribute.
 */
interface KeyMapper
{
    /**
     * Get matching input array key for given property.
     */
    public function getKey(string $property): string;
}
