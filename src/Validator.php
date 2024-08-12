<?php

namespace Formotron;

/**
 * Interface for validators.
 *
 * Validators are set on data object properties via the @see
 * Formotron\Attribute\Assert attribute.
 */
interface Validator
{
    /**
     * Validate an input value.
     *
     * The return value is a list of validation errors. Interpretation is up to
     * the application. Errors can be anything from message strings to symbolic
     * error codes.
     *
     * If the input value is valid, return an empty array.
     *
     * @return mixed[]
     */
    public function getValidationErrors(mixed $value): array;
}
