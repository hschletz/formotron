<?php

namespace Formotron\Test\PropertyTypes\Enum;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

enum PureTestEnum
{
    case Bar;
}

class PureTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValidEnum()
    {
        $dataObject = new class
        {
            public PureTestEnum $foo;
        };
        $result = $this->process(['foo' => PureTestEnum::Bar], $dataObject);
        $this->assertEquals(PureTestEnum::Bar, $result->foo);
    }

    public function testValidString()
    {
        $dataObject = new class
        {
            public PureTestEnum $foo;
        };
        $result = $this->process(['foo' => 'Bar'], $dataObject);
        $this->assertEquals(PureTestEnum::Bar, $result->foo);
    }

    public function testInvalid()
    {
        $dataObject = new class
        {
            public PureTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo: bar');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testInvalidValueType()
    {
        $dataObject = new class
        {
            public PureTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected string, got int');
        $this->process(['foo' => 42], $dataObject);
    }
}
