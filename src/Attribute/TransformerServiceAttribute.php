<?php

namespace Formotron\Attribute;

/**
 * Apply Transformer service to property.
 *
 * An attribute implementing this interface can be set on a property to
 * transform the input value into something different.
 */
interface TransformerServiceAttribute
{
    /**
     * Transformer service name.
     *
     * The returned string is passed to the container, which must resolve to an
     * object implementing the @see Formotron\Transformer interface.
     */
    public function getServiceName(): string;

    /**
     * Transformer arguments.
     *
     * The return value is passed as extra arguments to @see
     * Transformer::transform()
     *
     * @return mixed[]
     */
    public function getArguments(): array;
}
