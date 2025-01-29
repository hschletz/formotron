<?php

namespace Formotron\Test;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class MiscTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testMinimal()
    {
        $dataObject = new class
        {
        };
        $this->assertInstanceOf(get_class($dataObject), $this->process([], $dataObject));
    }

    public function testConstructorBypassed()
    {
        // Need to provide a constructor argument here, but $this->process()
        // will only evaluate the class name. Tested code will create a new
        // instance, bypassing the constructor.
        $dataObject = new class(null)
        {
            /** @phpstan-ignore constructor.unusedParameter */
            public function __construct(mixed $mandatoryArg)
            {
            }
        };
        $this->assertInstanceOf(get_class($dataObject), $this->process([], $dataObject));
    }

    public function testNonPublicProperty()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore property.unused */
            private mixed $foo;
        };
        $result = $this->process(['foo' => 'bar'], $dataObject);
        $property = new ReflectionProperty($result, 'foo');
        $this->assertEquals('bar', $property->getValue($result));
    }
}
