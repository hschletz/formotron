<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Add preprocessor to class.
 *
 * Set this attribute on a class to perform extra preprocessing of input data
 * before trying to populate the data object. The provided argument is passed to
 * the container, which must resolve to an object implementing the @see
 * Formotron\PreProcessor interface.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class PreProcess
{
    public function __construct(public readonly string $preProcessorService)
    {
    }
}
