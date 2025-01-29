<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\Test\DataProcessorTestTrait;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BoolTest extends TestCase
{
    use DataProcessorTestTrait;

    public static function validBoolProvider()
    {
        return [[true], [false]];
    }

    #[DataProvider('validBoolProvider')]
    public function testBoolPropertyValid(bool $value)
    {
        $dataObject = new class
        {
            public bool $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }


    public static function nullableProvider()
    {
        return [[true], [false], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testBoolPropertyNullable(?bool $value)
    {
        $dataObject = new class
        {
            public ?bool $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public static function invalidBoolProvider()
    {
        return [[0], [1], ['0'], ['1'], [''], [null]];
    }

    #[DataProvider('invalidBoolProvider')]
    public function testBoolPropertyInvalid(mixed $value)
    {
        $dataObject = new class
        {
            public bool $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected bool, got ' . gettype($value));
        $this->process(['foo' => $value], $dataObject);
    }
}
