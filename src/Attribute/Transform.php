<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Add transformation to property.
 *
 * Set this attribute on a property to transform the input value into something
 * different. The provided argument is passed to the container, which must
 * resolve to an object implementing the @see Formotron\Transformer interface.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Transform
{
    public function __construct(public readonly string $transformerService)
    {
    }
}
