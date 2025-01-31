<?php

namespace Formotron\Test\Attributes;

use Attribute;
use Error;
use Formotron\Attribute\Assert;
use Formotron\Attribute\Transform;
use Formotron\Test\DataProcessorTestTrait;
use Formotron\Transformer;
use Formotron\Validator;
use LogicException;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CustomAttribute extends Transform
{
    public function __construct(string $arg1, string $arg2)
    {
        parent::__construct('TestTransformer', $arg1, $arg2);
    }
}

class TransformTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testTransformAttribute()
    {
        $transformer = new class implements Transformer
        {
            public function transform(mixed $value, array $args): mixed
            {
                TestCase::assertEquals('a', $value);
                return 'b';
            }
        };
        $services = [['TestTransformer', $transformer]];

        $dataObject = new class
        {
            #[Transform('TestTransformer')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'a'], $dataObject, $services);
        $this->assertInstanceOf(get_class($dataObject), $result);
        $this->assertEquals('b', $result->foo);
    }

    public function testExtraPositionalArguments()
    {
        $transformer = $this->createMock(Transformer::class);
        $transformer->expects($this->once())->method('transform')->with($this->anything(), ['value1', 'value2']);
        $services = [['TestTransformer', $transformer]];

        $dataObject = new class
        {
            #[Transform('TestTransformer', 'value1', 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }

    public function testExtraNamedArguments()
    {
        $transformer = $this->createMock(Transformer::class);
        $transformer->expects($this->once())->method('transform')->with($this->anything(), ['arg1' => 'value1', 'arg2' => 'value2']);
        $services = [['TestTransformer', $transformer]];

        $dataObject = new class
        {
            #[Transform('TestTransformer', arg1: 'value1', arg2: 'value2')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }

    public function testCustomAttribute()
    {
        $transformer = new class implements Transformer
        {
            public function transform(mixed $value, array $args): mixed
            {
                TestCase::assertEquals('a', $value);
                TestCase::assertEquals(['value1', 'value2'], $args);

                return 'b';
            }
        };
        $services = [['TestTransformer', $transformer]];

        $dataObject = new class
        {
            #[CustomAttribute('value1', 'value2')]
            public mixed $foo;
        };
        $result = $this->process(['foo' => 'a'], $dataObject, $services);
        $this->assertEquals('b', $result->foo);
    }

    public function testMultipleTransformAttributes()
    {
        $dataObject = new class
        {
            #[Transform('foo')]
            #[Transform('bar')] // @phpstan-ignore attribute.nonRepeatable
            public mixed $foo;
        };
        $this->expectException(Error::class);
        $this->process(['foo' => 'bar'], $dataObject);
    }

    public function testTransformAttributeWithInvalidService()
    {
        $transformer = new stdClass();
        $services = [['Transformer', $transformer]];

        $dataObject = new class
        {
            #[Transform('Transformer')]
            public mixed $foo;
        };
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service Transformer does not implement ' . Transformer::class);
        $this->process(['foo' => 'bar'], $dataObject, $services);
    }

    public function testTransformedValueGetsValidated()
    {
        $transformer = new class implements Transformer
        {
            public function transform(mixed $value, array $args): mixed
            {
                return 'b';
            }
        };
        $validator = new class implements Validator
        {
            public function getValidationErrors(mixed $value): array
            {
                TestCase::assertEquals('b', $value);
                return [];
            }
        };
        $services = [
            ['TestTransformer', $transformer],
            ['TestValidator', $validator],
        ];

        $dataObject = new class
        {
            #[Transform('TestTransformer')]
            #[Assert('TestValidator')]
            public mixed $foo;
        };
        $this->process(['foo' => 'a'], $dataObject, $services);
    }
}
