<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Add postprocessor to class.
 *
 * Set this attribute on a class to perform extra postprocessing of the
 * populated and validated data object. The provided argument is passed to the
 * container, which must resolve to an object implementing the
 * @see Formotron\PostProcessor interface.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class PostProcess
{
    public function __construct(public readonly string $postProcessorService) {}
}
