<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class IntTest extends TestCase
{
    use DataProcessorTestTrait;

    public static function validIntProvider()
    {
        return [
            [0, 0],
            [123, 123],
            [-123, -123],
            ['1234567890', 1234567890],
            ['+123', 123],
            ['-123', -123],
            [
                new class {
                    public function __toString()
                    {
                        return '123';
                    }
                },
                123,
            ],
        ];
    }

    #[DataProvider('validIntProvider')]
    public function testIntPropertyValid(mixed $value, int $expectedValue)
    {
        $dataObject = new class
        {
            public int $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertEquals($expectedValue, $result->foo);
    }

    public static function nullableProvider()
    {
        return [[0], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testNullable(?int $value)
    {
        $dataObject = new class
        {
            public ?int $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testNullableWithInvalidType()
    {
        $dataObject = new class
        {
            public ?int $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected int or parseable string, got ');
        $this->process(['foo' => 1.23], $dataObject);
    }

    public static function invalidIntProvider()
    {
        return [
            [null],
            [true],
            [false],
            [[]],
            [new stdClass()],
            [1.0],
            [1.23],
            [''],
            ['123a'], // PHP would parse this as 123, but Formotron intentionally does not.
            ['1e3'], // PHP would parse this as 1000, but Formotron intentionally does not.
            ['0x12'], // PHP would parse this as 0, but Formotron intentionally does not.
            ['+'],
            ['-'],
            ['1+'],
            ['1-'],
        ];
    }

    #[DataProvider('invalidIntProvider')]
    public function testIntPropertyInvalid(mixed $value)
    {
        $dataObject = new class
        {
            public int $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected int or parseable string, got ');
        $this->process(['foo' => $value], $dataObject);
    }
}
