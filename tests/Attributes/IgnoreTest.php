<?php

namespace Formotron\Test\Attributes;

use Formotron\AssertionFailedException;
use Formotron\Attribute\Ignore;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class IgnoreTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testIgnoreAttribute()
    {
        $dataObject = new class
        {
            public mixed $foo;

            #[Ignore]
            public mixed $bar;
        };
        $result = $this->process(['foo' => 'baz'], $dataObject);
        $this->assertEquals('baz', $result->foo);

        $bar = new ReflectionProperty($result, 'bar');
        $this->assertFalse($bar->isInitialized($result));
    }

    public function testIgnoreAttributeWithKeyPresent()
    {
        $dataObject = new class
        {
            public mixed $foo;

            #[Ignore]
            public mixed $bar;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Input data contains extra keys: bar');
        $this->process(['foo' => 'baz', 'bar' => 'baz'], $dataObject);
    }

    public function testIgnoreAttributeWithDefaultValue()
    {
        $dataObject = new class
        {
            public mixed $foo;

            #[Ignore]
            public mixed $bar = 'foobar';
        };
        $result = $this->process(['foo' => 'baz'], $dataObject);
        $this->assertEquals('baz', $result->foo);
        $this->assertEquals('foobar', $result->bar);
    }

    public function testIgnoreAttributeWithDefaultValueAndKeyPresent()
    {
        $dataObject = new class
        {
            public mixed $foo;

            #[Ignore]
            public mixed $bar = 'foobar';
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Input data contains extra keys: bar');
        $this->process(['foo' => 'baz', 'bar' => 'baz'], $dataObject);
    }
}
