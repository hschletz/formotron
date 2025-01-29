<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ObjectTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testObjectProperty()
    {
        $dataObject = new class
        {
            public object $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot handle properties of type object');
        $this->process(['foo' => new stdClass()], $dataObject);
    }
}
