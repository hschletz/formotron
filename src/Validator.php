<?php

namespace Formotron;

/**
 * Interface for validators.
 *
 * Validators are set on data object properties via the @see
 * Formotron\Attribute\ValidatorServiceAttribute attribute.
 */
interface Validator
{
    /**
     * Validate an input value.
     *
     * If the input value is invalid, throw an exception.
     *
     * $args contains the extra arguments passed to the @see
     * ValidatorServiceAttribute attribute.
     *
     * @param mixed[] $args
     */
    public function validate(mixed $value, array $args): void;
}
