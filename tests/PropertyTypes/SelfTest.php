<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

class SelfTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testSelfProperty()
    {
        $dataObject = new class
        {
            public self $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot handle properties of type self');
        $this->process(['foo' => new stdClass()], $dataObject);
    }
}
