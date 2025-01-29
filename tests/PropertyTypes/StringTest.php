<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

class StringTest extends TestCase
{
    use DataProcessorTestTrait;

    public static function validStringProvider()
    {
        return [
            ['bar', 'bar'],
            [42, '42'],
            [3.14, '3.14'],
            [
                new class implements Stringable
                {
                    public function __toString(): string
                    {
                        return 'bar';
                    }
                },
                'bar',
            ],
            [
                new class
                {
                    public function __toString(): string
                    {
                        return 'bar';
                    }
                },
                'bar',
            ],
        ];
    }

    #[DataProvider('validStringProvider')]
    public function testStringPropertyValid(mixed $value, string $expectedValue)
    {
        $dataObject = new class
        {
            public string $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertEquals($expectedValue, $result->foo);
    }

    public static function nullableProvider()
    {
        return [[''], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testNullable(?string $value)
    {
        $dataObject = new class
        {
            public ?string $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testNullableWithInvalidType()
    {
        $dataObject = new class
        {
            public ?string $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected string|int|float|Stringable, got ');
        $this->process(['foo' => new stdClass()], $dataObject);
    }

    public static function invalidStringProvider()
    {
        return [
            [null],
            [true],
            [false],
            [[]],
            [new stdClass()],
        ];
    }

    #[DataProvider('invalidStringProvider')]
    public function testStringPropertyInvalid(mixed $value)
    {
        $dataObject = new class
        {
            public string $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected string|int|float|Stringable, got ');
        $this->process(['foo' => $value], $dataObject);
    }
}
