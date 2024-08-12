<?php

namespace Formotron;

/**
 * Interface for preprocessors.
 *
 * Preprocessors are set on the data object class via the @see
 * Formotron\Attribute\PreProcess attribute.
 */
interface PreProcessor
{
    /**
     * Preprocess input data.
     *
     * Implementations can do anything with the input data: leave it as is,
     * modify it, throw an exception, whatever. The returned array is fed to the
     * next preprocessor, if any, and finally used to populate the data object.
     *
     * @param mixed[] $formData
     * @return mixed[]
     */
    public function process(array $formData): array;
}
