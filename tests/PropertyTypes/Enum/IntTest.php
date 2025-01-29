<?php

namespace Formotron\Test\PropertyTypes\Enum;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

enum IntBackedTestEnum: int
{
    case Bar = 42;
}

class IntTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValidInt()
    {
        $dataObject = new class
        {
            public IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => 42], $dataObject);
        $this->assertEquals(IntBackedTestEnum::Bar, $result->foo);
    }

    public function testValidString()
    {
        $dataObject = new class
        {
            public IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => '42'], $dataObject);
        $this->assertEquals(IntBackedTestEnum::Bar, $result->foo);
    }

    public function testInvalidString()
    {
        $dataObject = new class
        {
            public IntBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected int|int-string, got string');
        $this->process(['foo' => '42a'], $dataObject);
    }

    public function testInvalidValueType()
    {
        $dataObject = new class
        {
            public IntBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected int|int-string, got array');
        $this->process(['foo' => []], $dataObject);
    }
}
