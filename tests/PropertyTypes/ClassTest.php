<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ClassTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testClassPropertyValidWithSameClass()
    {
        $dataObject = new class
        {
            public stdClass $foo;
        };
        $value = new stdClass();
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testClassPropertyValidWithSubclass()
    {
        $dataObject = new class
        {
            public stdClass $foo;
        };
        $value = new class extends stdClass
        {
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testClassPropertyInvalidType()
    {
        $dataObject = new class
        {
            public stdClass $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected stdClass, got string');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public static function nullableProvider()
    {
        return [[new stdClass()], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testNullable(?stdClass $value)
    {
        $dataObject = new class
        {
            public ?stdClass $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testNullableWithInvalidType()
    {
        $dataObject = new class
        {
            public ?stdClass $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected stdClass, got ');
        $this->process(['foo' => ''], $dataObject);
    }
}
