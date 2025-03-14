<?php

namespace Formotron\Test\PropertyTypes\Enum;

use Formotron\AssertionFailedException;
use Formotron\Attribute\UseBackingValue;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum IntBackedTestEnum: int
{
    case Bar = 42;
}

class BackedIntTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValidEnum()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => IntBackedTestEnum::Bar], $dataObject);
        $this->assertEquals(IntBackedTestEnum::Bar, $result->foo);
    }

    public function testValidInt()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => 42], $dataObject);
        $this->assertEquals(IntBackedTestEnum::Bar, $result->foo);
    }

    public function testValidString()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => '42'], $dataObject);
        $this->assertEquals(IntBackedTestEnum::Bar, $result->foo);
    }

    public function testInvalidString()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
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
            #[UseBackingValue]
            public IntBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected int|int-string, got array');
        $this->process(['foo' => []], $dataObject);
    }

    public static function nullableProvider()
    {
        return [[IntBackedTestEnum::Bar], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testNullable(?IntBackedTestEnum $value)
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public ?IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testNullableWithInvalidType()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public ?IntBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('alue for $foo has invalid type, expected int|int-string, got ');
        $this->process(['foo' => ''], $dataObject);
    }
}
