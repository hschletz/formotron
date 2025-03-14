<?php

namespace Formotron\Test\PropertyTypes\Enum;

use Formotron\AssertionFailedException;
use Formotron\Attribute\UseBackingValue;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum StringBackedTestEnum: string
{
    case Bar = 'bar';
}

class BackedStringTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValidWithEnum()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public StringBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => StringBackedTestEnum::Bar], $dataObject);
        $this->assertEquals(StringBackedTestEnum::Bar, $result->foo);
    }

    public function testValidWithString()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public StringBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => 'bar'], $dataObject);
        $this->assertEquals(StringBackedTestEnum::Bar, $result->foo);
    }

    public function testValidWithStringable()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public StringBackedTestEnum $foo;
        };
        $value = new class
        {
            public function __toString()
            {
                return 'bar';
            }
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertEquals(StringBackedTestEnum::Bar, $result->foo);
    }

    public function testInvalid()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public StringBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo: Bar');
        $this->process(['foo' => 'Bar'], $dataObject);
    }

    public function testInvalidValueType()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public StringBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected stringable, got array');
        $this->process(['foo' => []], $dataObject);
    }

    public static function nullableProvider()
    {
        return [[StringBackedTestEnum::Bar], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testNullable(?StringBackedTestEnum $value)
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public ?StringBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testNullableWithInvalidType()
    {
        $dataObject = new class
        {
            #[UseBackingValue]
            public ?StringBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo');
        $this->process(['foo' => ''], $dataObject);
    }
}
