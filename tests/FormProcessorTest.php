<?php

namespace Formotron\Test;

use Formotron\AssertionFailedException;
use Formotron\Attribute\Assert;
use Formotron\Attribute\PreProcess;
use Formotron\FormProcessor;
use Formotron\PreProcessor;
use Formotron\Validator;
use Iterator;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use stdClass;
use Traversable;

enum PureTestEnum
{
    case Bar;
}

enum StringBackedTestEnum: string
{
    case Bar = 'bar';
}

enum IntBackedTestEnum: int
{
    case Bar = 42;
}

class FormProcessorTest extends TestCase
{
    /**
     * @template T of object
     * @param mixed[] $input
     * @param T $dataObject
     * @param list<list{string, object}> $services
     * @return T
     */
    private function process(array $input, object $dataObject, array $services = []): object
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnMap($services);

        $formProcessor = new FormProcessor($container);
        $result = $formProcessor->process($input, get_class($dataObject));

        return $result;
    }

    public static function mixedTypeProvider()
    {
        return [
            ['foo'],
            [true],
            [42],
            [1.234],
            [[]],
            [new stdClass()],
        ];
    }

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

    #[DataProvider('mixedTypeProvider')]
    public function testTypelessProperty(mixed $value)
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.property */
            public $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertEquals($value, $result->foo);
    }

    #[DataProvider('mixedTypeProvider')]
    public function testMixedProperty(mixed $value)
    {
        $dataObject = new class
        {
            public mixed $foo;
        };
        $result = $this->process(['foo' => $value], $dataObject);
        $this->assertEquals($value, $result->foo);
    }

    public function testStringProperty()
    {
        $dataObject = new class
        {
            public string $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of string properties is not implemented yet');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testBoolProperty()
    {
        $dataObject = new class
        {
            public bool $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of bool properties is not implemented yet');
        $this->process(['foo' => true], $dataObject);
    }

    public function testIntProperty()
    {
        $dataObject = new class
        {
            public int $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of int properties is not implemented yet');
        $this->process(['foo' => 42], $dataObject);
    }

    public function testFloatProperty()
    {
        $dataObject = new class
        {
            public float $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of float properties is not implemented yet');
        $this->process(['foo' => 1.234], $dataObject);
    }

    public function testIterableProperty()
    {
        $dataObject = new class
        {
            /** @phpstan-ignore missingType.iterableValue */
            public iterable $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Handling of iterable properties is not implemented yet');
        $this->process(['foo' => []], $dataObject);
    }

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

    public function testObjectProperty()
    {
        $dataObject = new class
        {
            public object $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot handle properties of type object');
        $this->process(['foo' => new stdClass()], $dataObject);
    }

    public function testSelfProperty()
    {
        $dataObject = new class
        {
            public self $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot handle properties of type self');
        $this->process(['foo' => new stdClass()], $dataObject);
    }

    public function testParentProperty()
    {
        $dataObject = new class extends stdClass
        {
            public parent $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot handle properties of type parent');
        $this->process(['foo' => new stdClass()], $dataObject);
    }

    public function testPureEnumPropertyValid()
    {
        $dataObject = new class
        {
            public PureTestEnum $foo;
        };
        $result = $this->process(['foo' => 'Bar'], $dataObject);
        $this->assertEquals(PureTestEnum::Bar, $result->foo);
    }

    public function testPureEnumPropertyInvalid()
    {
        $dataObject = new class
        {
            public PureTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo: bar');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testPureEnumWithInvalidValueType()
    {
        $dataObject = new class
        {
            public PureTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected string, got int');
        $this->process(['foo' => 42], $dataObject);
    }

    public function testStringBackedEnumPropertyValid()
    {
        $dataObject = new class
        {
            public StringBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => 'bar'], $dataObject);
        $this->assertEquals(StringBackedTestEnum::Bar, $result->foo);
    }

    public function testStringBackedEnumPropertyInvalid()
    {
        $dataObject = new class
        {
            public StringBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo: Bar');
        $this->process(['foo' => 'Bar'], $dataObject);
    }

    public function testStringBackedEnumWithInvalidValueType()
    {
        $dataObject = new class
        {
            public StringBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Value for $foo has invalid type, expected string|int, got array');
        $this->process(['foo' => []], $dataObject);
    }

    public function testIntBackedEnumPropertyValidInt()
    {
        $dataObject = new class
        {
            public IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => 42], $dataObject);
        $this->assertEquals(IntBackedTestEnum::Bar, $result->foo);
    }

    public function testIntBackedEnumPropertyValidString()
    {
        $dataObject = new class
        {
            public IntBackedTestEnum $foo;
        };
        $result = $this->process(['foo' => '42'], $dataObject);
        $this->assertEquals(IntBackedTestEnum::Bar, $result->foo);
    }

    public function testIntBackedEnumPropertyInvalidString()
    {
        $dataObject = new class
        {
            public IntBackedTestEnum $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid value for $foo: 42a');
        $this->process(['foo' => '42a'], $dataObject);
    }

    public function testUnionProperty()
    {
        $dataObject = new class
        {
            public string | int $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Union/intersection types are not supported');
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testIntersectionProperty()
    {
        $dataObject = new class
        {
            public Iterator & Traversable $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Union/intersection types are not supported');
        $this->process(['foo' => 'bar'], $dataObject);
    }

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

    public function testPreProcessAttributes()
    {
        $preProcessor1 = new class implements PreProcessor
        {
            public function process(array $formData): array
            {
                unset($formData['key1']);
                return $formData;
            }
        };
        $preProcessor2 = new class implements PreProcessor
        {
            public function process(array $formData): array
            {
                unset($formData['key2']);
                return $formData;
            }
        };
        $services = [
            ['PreProcessor1', $preProcessor1],
            ['PreProcessor2', $preProcessor2],
        ];


        $dataObject = new #[PreProcess('PreProcessor1')] #[PreProcess('PreProcessor2')] class
        {
        };
        $result = $this->process(['key1' => '', 'key2' => ''], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertObjectNotHasProperty('key1', $result);
        $this->assertObjectNotHasProperty('key2', $result);
    }

    public function testPreProcessAttributWithInvalidService()
    {
        $preProcessor = new stdClass();
        $services = [['PreProcessor', $preProcessor]];

        $dataObject = new #[PreProcess('PreProcessor')] class
        {
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service PreProcessor does not implement ' . PreProcessor::class);
        $this->process(['key1' => ''], $dataObject, $services);
    }

    public function testProcessAssertionsPass()
    {
        $validator1 = $this->createMock(Validator::class);
        $validator1->expects($this->once())->method('getValidationErrors')->with('bar')->willReturn([]);

        $validator2 = $this->createMock(Validator::class);
        $validator2->expects($this->once())->method('getValidationErrors')->with('bar')->willReturn([]);

        $services = [
            ['Validator1', $validator1],
            ['Validator2', $validator2],
        ];

        $dataObject = new class
        {
            #[Assert('Validator1')]
            #[Assert('Validator2')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'bar'], $dataObject, $services);
        $this->assertEquals('bar', $result->foo);
    }

    public function testProcessAssertionsFails()
    {
        $validator = $this->createMock(Validator::class);
        $validator->method('getValidationErrors')->willReturn(['error1', 'error2']);
        $services = [['Validator', $validator]];

        $dataObject = new class
        {
            #[Assert('Validator')]
            public mixed $foo;
        };
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Assertion Validator failed on $foo');
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testProcessAssertionsWithInvalidService()
    {
        $validator = new stdClass();
        $services = [['Validator', $validator]];

        $dataObject = new class
        {
            #[Assert('Validator')]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service Validator does not implement ' . Validator::class);
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }
}
