<?php

namespace Formotron\Attribute;

use Attribute;

/**
 * Add assertion to property.
 *
 * Set this attribute on a property to perform extra assertions. The
 * $validatorService argument is passed to the container, which must resolve to
 * an object implementing the @see Formotron\Validator interface. Additional
 * arguments are passed to @see Validator::getValidationErrors().
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Assert
{
    /**
     * @var mixed[] $args
     */
    public readonly array $args;

    public function __construct(public readonly string $validatorService, mixed ...$args)
    {
        $this->args = $args;
    }
}
