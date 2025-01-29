<?php

namespace Formotron\Test\Attributes;

use Error;
use Formotron\AssertionFailedException;
use Formotron\Attribute\Key;
use Formotron\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class KeyTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testKeyAttribute()
    {
        $dataObject = new class
        {
            #[Key('bar')]
            public mixed $foo;
        };
        $result = $this->process(['bar' => 'baz'], $dataObject);
        $this->assertEquals('baz', $result->foo);
    }

    public function testMultipleKeyAttributes()
    {
        $dataObject = new class
        {
            #[Key('foo')]
            #[Key('bar')] // @phpstan-ignore attribute.nonRepeatable
            public mixed $foo;
        };
        $this->expectException(Error::class);
        $this->process(['bar' => 'baz'], $dataObject);
    }

    public function testKeyAttributeWithBothKeys()
    {
        $dataObject = new class
        {
            #[Key('bar')]
            public mixed $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Input data contains extra keys: foo');
        $this->process(['foo' => 'baz', 'bar' => 'baz'], $dataObject);
    }

    public function testKeyAttributeWithPropertyNameSetOnDifferentProperty()
    {
        $dataObject = new class
        {
            #[Key('bar')]
            public mixed $foo;

            #[Key('foo')]
            public mixed $baz;
        };
        $result = $this->process(['bar' => 'Bar', 'foo' => 'Foo'], $dataObject);
        $this->assertEquals('Bar', $result->foo);
        $this->assertEquals('Foo', $result->baz);
    }
}
