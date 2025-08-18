<?php

namespace Formotron\Attribute;

/**
 * Apply transformation to property.
 *
 * An attribute implementing this interface can be set on a property to
 * transform the input value into something different.
 */
interface TransformerAttribute
{
    /**
     * Apply transformation to given value.
     *
     * This method may use extra arguments passed in through the constructor.
     */
    public function transform(mixed $value): mixed;
}
