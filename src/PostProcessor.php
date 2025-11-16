<?php

namespace Formotron;

/**
 * Interface for postprocessors.
 *
 * Postprocessors are set on the data object class via the
 * @see Formotron\Attribute\PostProcess attribute.
 */
interface PostProcessor
{
    /**
     * Postprocess the populated and validated data object.
     *
     * Implementations can do anything with the data object: leave it as is,
     * modify it, throw an exception, whatever.
     */
    public function process(object $dataObject): void;
}
