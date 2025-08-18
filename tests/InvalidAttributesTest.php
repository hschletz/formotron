<?php

namespace Formotron\Test;

use Attribute;
use Formotron\Attribute\TransformerAttribute;
use Formotron\Attribute\TransformerServiceAttribute;
use LogicException;
use PHPUnit\Framework\TestCase;

#[Attribute(Attribute::TARGET_PROPERTY)]
class InvalidTransformerAttribute implements TransformerAttribute, TransformerServiceAttribute
{
    public function transform(mixed $value): mixed
    {
        return null;
    }

    public function getServiceName(): string
    {
        return '';
    }

    public function getArguments(): array
    {
        return [];
    }
}

class InvalidAttributesTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testInvalidTransformerAttribute()
    {
        $dataObject = new class
        {
            #[InvalidTransformerAttribute]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/^Attribute .* but not both$/');
        $this->process(['foo' => ''], $dataObject);
    }
}
