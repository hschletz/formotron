<?php

namespace Formotron\Test\PropertyTypes\Enum;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

enum BackedTestEnum: string
{
    case Bar = 'baz';
}

class BackedUseNameTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValidEnum()
    {
        $dataObject = new class
        {
            public BackedTestEnum $foo;
        };
        $result = $this->process(['foo' => BackedTestEnum::Bar], $dataObject);
        $this->assertEquals(BackedTestEnum::Bar, $result->foo);
    }

    public function testValidString()
    {
        $dataObject = new class
        {
            public BackedTestEnum $foo;
        };
        $result = $this->process(['foo' => 'Bar'], $dataObject);
        $this->assertEquals(BackedTestEnum::Bar, $result->foo);
    }

    public function testInvalid()
    {
        $dataObject = new class
        {
            public BackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo: baz');
        $this->process(['foo' => 'baz'], $dataObject);
    }

    public function testInvalidValueType()
    {
        $dataObject = new class
        {
            public BackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected string, got int');
        $this->process(['foo' => 42], $dataObject);
    }

    public static function nullableProvider()
    {
        return [[BackedTestEnum::Bar], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testNullable(?BackedTestEnum $value)
    {
        $dataObject = new class
        {
            public ?BackedTestEnum $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testNullableWithInvalidType()
    {
        $dataObject = new class
        {
            public ?BackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo');
        $this->process(['foo' => ''], $dataObject);
    }
}
