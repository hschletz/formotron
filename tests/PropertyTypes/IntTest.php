<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;

class IntTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testIntProperty()
    {
        $dataObject = new class
        {
            public int $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of int properties is not implemented yet');
        $this->process(['foo' => 42], $dataObject);
    }
}
