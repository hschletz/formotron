<?php

namespace Formotron\Attribute;

use Attribute;
use Override;

/**
 * Apply Transformer service to property.
 *
 * Set this attribute on a property to transform the input value into something
 * different, based on the provided Transformer service.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Transform implements TransformerServiceAttribute
{
    /** @var mixed[] */
    private array $arguments;

    /**
     * @param string $serviceName Service name passed to the container
     * @param mixed ...$arguments Extra arguments passed to Transformer::transform()
     */
    public function __construct(private string $serviceName, mixed ...$arguments)
    {
        $this->arguments = $arguments;
    }

    #[Override]
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    #[Override]
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
