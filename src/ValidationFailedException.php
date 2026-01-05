<?php

namespace Formotron;

use InvalidArgumentException;

/**
 * Bulk reporting of "soft" validation errors.
 *
 * This is thrown by the data processor when at least 1 validator threw a @see
 * ValidationError. The $errors property will contain an array with the property
 * name as key and the reported details as value.
 */
final class ValidationFailedException extends InvalidArgumentException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(public readonly array $errors)
    {
        assert(!empty($errors));
        assert(!array_is_list($errors));

        parent::__construct('Validation failed, see exception details');
    }
}
