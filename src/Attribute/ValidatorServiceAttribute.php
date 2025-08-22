<?php

namespace Formotron\Attribute;

/**
 * Apply Validator service to property.
 *
 * An attribute implementing this interface can be set on a property to have the
 * given service validate the input value.
 */
interface ValidatorServiceAttribute
{
    /**
     * Validator service name.
     *
     * The returned string is passed to the container, which must resolve to an
     * object implementing the @see Formotron\Validator interface.
     */
    public function getServiceName(): string;

    /**
     * Validator arguments.
     *
     * The return value is passed as extra arguments to @see
     * Validator::validate()
     *
     * @return mixed[]
     */
    public function getArguments(): array;
}
