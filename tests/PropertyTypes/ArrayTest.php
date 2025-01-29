<?php

namespace Formotron\Test\PropertyTypes;

use Formotron\AssertionFailedException;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class ArrayTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testArrayPropertyValid()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.iterableValue */
            public array $foo;
        };
        $result = $this->process(['foo' => ['test']], $dataObject);
        $this->assertEquals(['test'], $result->foo);
    }

    public function testArrayPropertyInvalidType()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.iterableValue */
            public array $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected array, got string');
        $this->process(['foo' => 'test'], $dataObject);
    }
}
