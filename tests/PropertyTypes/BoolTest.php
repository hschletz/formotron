<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;

class BoolTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testBoolProperty()
    {
        $dataObject = new class
        {
            public bool $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of bool properties is not implemented yet');
        $this->process(['foo' => true], $dataObject);
    }
}
