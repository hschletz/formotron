<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class ParentTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testParentProperty()
    {
        $dataObject = new class extends stdClass
        {
            public parent $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot handle properties of type parent');
        $this->process(['foo' => new stdClass()], $dataObject);
    }
}
