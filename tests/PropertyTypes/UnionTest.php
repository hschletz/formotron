<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\TestCase;

class UnionTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testUnionProperty()
    {
        $dataObject = new class
        {
            public string | int $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Union/intersection types are not supported');
        $this->process(['foo' => 'bar'], $dataObject);
    }
}
