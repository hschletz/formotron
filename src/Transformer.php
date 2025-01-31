<?php

namespace Formotron;

/**
 * Interface for transformers.
 *
 * Transformers are set on data object properties via the @see
 * Formotron\Attribute\Transform attribute.
 */
interface Transformer
{
    /**
     * Transform an input value.
     *
     * Implementations can do anything with the input value: leave it as is,
     * modify it, throw an exception, whatever. The returned value is still
     * subject to the property's validation rules.
     *
     * $args contains the extra arguments passed to Transform attribute.
     *
     * @param mixed[] $args
     */
    public function transform(mixed $value, array $args): mixed;
}
