<?php

namespace Formotron\Test;

use Formotron\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class KeysTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testMissingKeyForTypedProperty()
    {
        $dataObject = new class
        {
            public mixed $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Missing key: foo');
        $this->process([], $dataObject);
    }

    public function testMissingKeyForTypelessProperty()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.property */
            public $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Missing key: foo');
        $this->process([], $dataObject);
    }

    public function testExtraKey()
    {
        $dataObject = new class
        {
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Input data contains extra keys: foo, bar');
        $this->process(['foo' => '', 'bar' => ''], $dataObject);
    }
}
