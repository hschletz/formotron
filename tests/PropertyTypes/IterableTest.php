<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;

class IterableTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testIterableProperty()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.iterableValue */
            public iterable $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of iterable properties is not implemented yet');
        $this->process(['foo' => []], $dataObject);
    }
}
