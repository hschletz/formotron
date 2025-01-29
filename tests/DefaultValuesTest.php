<?php

namespace Formotron\Test;

use Formotron\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class DefaultValuesTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDefaultValueForTypedProperty()
    {
        $dataObject = new class
        {
            public mixed $foo = 'bar';
        };
        $result = $this->process([], $dataObject);
        $this->assertEquals('bar', $result->foo);
    }

    public function testDefaultValueForTypelessProperty()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.property */
            public $foo = 'bar';
        };
        $result = $this->process([], $dataObject);
        $this->assertEquals('bar', $result->foo);
    }

    public function testDefaultNullForTypedProperty()
    {
        $dataObject = new class
        {
            public mixed $foo = null;
        };
        $result = $this->process([], $dataObject);
        $this->assertNull($result->foo);
    }

    public function testDefaultNullForTypelessProperty()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.property */
            public $foo = null;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Missing key: foo');
        $this->process([], $dataObject);
    }
}
