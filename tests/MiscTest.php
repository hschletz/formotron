<?php

namespace Formotron\Test;

use Formotron\DataProcessor;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class MiscTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testMinimal()
    {
        $dataObject = new class {};
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
            public function __construct(mixed $mandatoryArg) {}
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

    public function testIterate()
    {
        $input = [['foo' => 'bar'], ['foo' => 'baz']];
        $dataObject = new class {
            public mixed $foo;
        };
        $class = get_class($dataObject);

        $dataProcessor = $this->createPartialMock(DataProcessor::class, ['process']);
        $dataProcessor
            ->expects($this->exactly(2))
            ->method('process')
            ->with($this->anything(), $class)
            ->willReturnCallback(function ($value) use ($class) {
                $object = new $class;
                $object->foo = $value['foo'];

                return $object;
            });

        $iterable = $dataProcessor->iterate($input, $class);
        $result = iterator_to_array($iterable);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf($class, $result);
        $this->assertEquals('bar', $result[0]->foo);
        $this->assertEquals('baz', $result[1]->foo);
    }
}
