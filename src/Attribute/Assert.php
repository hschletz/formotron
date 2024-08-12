<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Add assertion to property.
 *
 * Set this attribute on a property to perform extra assertions. The provided
 * argument is passed to the container, which must resolve to an object
 * implementing the @see Formotron\Validator interface.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Assert
{
    public function __construct(public readonly string $validatorService)
    {
    }
}
