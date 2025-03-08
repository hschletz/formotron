<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

interface TestInterface {}

class InterfaceTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testInterfacePropertyValid()
    {
        $dataObject = new class
        {
            public TestInterface $foo;
        };
        $value = new class implements TestInterface {};
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testInterfacePropertyInvalidType()
    {
        $dataObject = new class
        {
            public TestInterface $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage(sprintf('Value for $foo has invalid type, expected %s, got object', TestInterface::class));
        $this->process(['foo' => new stdClass], $dataObject);
    }

    public static function nullableProvider()
    {
        return [[new class implements TestInterface {}], [null]];
    }

    #[DataProvider('nullableProvider')]
    public function testNullable(?TestInterface $value)
    {
        $dataObject = new class
        {
            public ?TestInterface $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertSame($value, $result->foo);
    }

    public function testNullableWithInvalidType()
    {
        $dataObject = new class
        {
            public ?TestInterface $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage(sprintf('Value for $foo has invalid type, expected %s, got object', TestInterface::class));
        $this->process(['foo' => new stdClass()], $dataObject);
    }
}
