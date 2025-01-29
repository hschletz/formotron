<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;

class FloatTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testFloatProperty()
    {
        $dataObject = new class
        {
            public float $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of float properties is not implemented yet');
        $this->process(['foo' => 1.234], $dataObject);
    }
}
