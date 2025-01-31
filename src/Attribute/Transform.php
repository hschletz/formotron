<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Add transformation to property.
 *
 * Set this attribute on a property to transform the input value into something
 * different. The $transformerService argument is passed to the container, which
 * must resolve to an object implementing the @see Formotron\Transformer
 * interface. Additional arguments are passed to @see Transformer::transform().
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Transform
{
    /**
     * @var mixed[] $args
     */
    public readonly array $args;

    /**
     * @param mixed[] $args
     */
    public function __construct(public readonly string $transformerService, ...$args)
    {
        $this->args = $args;
    }
}
