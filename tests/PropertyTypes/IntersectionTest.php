<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use Iterator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Traversable;

class IntersectionTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testIntersectionProperty()
    {
        $dataObject = new class
        {
            public Iterator & Traversable $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Union/intersection types are not supported');
        $this->process(['foo' => 'bar'], $dataObject);
    }
}
