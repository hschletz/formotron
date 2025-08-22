<?php

namespace Formotron\Attribute;

/**
 * Apply validation to property.
 *
 * An attribute implementing this interface can be set on a property to validate
 * the input value. Implementations must throw an exception for invalid values.
 */
interface ValidatorAttribute
{
    public function validate(mixed $value): void;
}
