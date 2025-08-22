<?php

namespace Formotron\Attribute;

use Attribute;
use Override;

/**
 * Apply Validator service to property.
 *
 * Set this attribute on a property to perform extra validations. The
 * $validatorService argument is passed to the container, which must resolve to
 * an object implementing the @see Formotron\Validator interface. Additional
 * arguments are passed to @see Validator::validate().
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class Validate implements ValidatorServiceAttribute
{
    /**
     * @var mixed[]
     */
    public readonly array $arguments;

    public function __construct(private string $validatorService, mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }

    #[Override]
    public function getServiceName(): string
    {
        return $this->validatorService;
    }

    #[Override]
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
