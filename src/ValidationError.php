<?php

namespace Formotron;

use InvalidArgumentException;

/**
 * "Soft" validation error.
 *
 * Validators can throw this exception for "soft" validation failures. Unlike
 * with other throwables, the data processor will proceed with the next property
 * and finally report all errors in bulk by throwing a @see
 * ValidationFailedException.
 *
 * The constructor argument can be anything: typically a string describing the
 * problem (optionally translated by the application's I18n framework), an array
 * of messages, an error object... Interpretation is left to the calling code.
 */
final class ValidationError extends InvalidArgumentException
{
    public function __construct(public readonly mixed $details)
    {
        parent::__construct(is_string($details) ? $details : 'Validation failed, see exception details');
    }
}
